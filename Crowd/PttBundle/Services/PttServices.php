<?php

namespace Crowd\PttBundle\Services;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Crowd\PttBundle\Util\PttCache;
use Crowd\PttBundle\Util\PttUtil;

use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;

use Symfony\Component\Security\Core\Util\SecureRandom;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;

use Doctrine\ORM\Tools\Pagination\Paginator;

class PttServices
{
    private $em;
    private $request;
    private $kernel;
    private $limit = 6;
    private $uploadsUrl;
    private $model = '';
    private $bundle;

    public function __construct(\Doctrine\ORM\EntityManager $em, KernelInterface $kernel) {
        $this->em = $em;
        $this->kernel = $kernel;
        
        try {
            $yaml = new Parser();
            $ptt = $yaml->parse(file_get_contents(__DIR__ . '/../../../../../../app/config/ptt.yml'));
            $this->uploadsUrl = (isset($ptt['s3']['force']) && $ptt['s3']['force']) ? $ptt['s3']['prodUrl'] . $ptt['s3']['dir'] . '/' : '/uploads/';
            $this->bundle = $ptt['bundles'][0]['bundle'];
        } catch (ParseException $e) {
            printf("Unable to parse the YAML string: %s", $e->getMessage());
        }
    }

    public function setRequest(RequestStack $request_stack){
        $this->request = $request_stack->getCurrentRequest();
    }

    private function _sql($table, $lang, $params){

        $qb = $this->em->createQueryBuilder();
        $tableBundle = $this->_getTableBundle($table);

        $qb->select(['t'])->from($tableBundle, 't');

        if(isset($params['where'])){ 
            $qb = $this->_where($params['where'], $qb);
        }


        if(isset($params['order'])){
            foreach ($params['order'] as $key => $order) {
                $qb->orderBy('t.' . $order['order'], $order['orderDir']);
             } 
        } else {
            $col = $this->em->getClassMetadata($tableBundle)->getFieldNames();
            if(array_search('_order', $col)){
                $qb->orderBy('t._order');
            }
        }

        if(isset($params['page'])){
            $limit = (isset($params['limit'])) ? $params['limit'] : $this->limit;
            $offset = $params['page'] * $limit;
            
            $qb->setMaxResults($limit);
            $qb->setFirstResult($offset);
        }

        return $qb;
    }

    private function _where($where, $qb){
        foreach ($where as $line) {
            foreach ($line as $key => $lines) {
                $key = strtoupper($key);
                $columns = [];
                foreach ($lines as $whereLine) {
                    if ($whereLine['column'] == 'direct-sql-injection'){
                        $columns[] = $whereLine['value'];
                    } else {
                        $value = (strtolower($whereLine['operator']) == 'in') ?  '('.$whereLine['value'].') ' : "'".$whereLine['value']."'";
                        $columns[] = 't.'.$whereLine['column'].' '.$whereLine['operator'].' '. $value;
                    }
                    $qb->andWhere(implode(' ' . $key . ' ', $columns));
                }
            }
        }

        return $qb;
    }

    public function get($table, $lang, $params = []){
        $qb = $this->_sql($table, $lang, $params);
        $query = $qb->getQuery();
        
        if(isset($params['as_array']) && $params['as_array']){ 
            $data = $query->getArrayResult();  
        } elseif(isset($params['one']) && $params['one']){    
            $data = $query->getSingleResult();
        } else {
            $data = $query->getResult();
        }
        
        return $data;
    }

    public function getSimpleFilter($table, $params = []){
        $where = (isset($params['where'])) ? $params['where'] : [];
        $orderBy = (isset($params['orderBy'])) ? $params['orderBy'] : [];

        return $this->em->getRepository($this->_getTableBundle($table))->findBy($where, $orderBy);
    }

    public function getAll($table, $params = []){
        return $this->em->getRepository($this->_getTableBundle($table))->findAll();
    }

    public function getOne($table, $id, $params = []){
        return $this->em->getRepository($this->_getTableBundle($table))->find($id);
    }

    public function getByPag($table, $lang, $params = []){
        $page = (isset($params['page'])) ? $params['page'] : 0;
        $limit = (isset($params['limit'])) ? $params['limit'] : $this->limit;

        $qb = $this->_sql($table, $lang, $params);
        $query = $qb->getQuery();

        $paginator = new Paginator($query);

        $paginator->getQuery()
            ->setFirstResult($limit * $page) // Offset
            ->setMaxResults($limit); // Limit

        $maxPages = ceil($paginator->count() / $limit);
        $hasNewPages = ($maxPages >= $page) ? false : true;

        $data = [];
        foreach ($paginator->getIterator() as $key => $row) {
            $data[] = $row;
        }
        
        return ['content' => $data, 'newPage' => $hasNewPages, 'limit' => $limit, 'maxPages' => $maxPages];
    }

    public function update($table, $id, $data){
        $row = $this->em->getRepository($this->_getTableBundle($table))->find($id);

        if(!$row){
            return false;
        }

        foreach ($data as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($row, $method)){
                $row->$method($value);
            }
        }

        $this->em->flush();
        return true;
    }

    public function create($object){
        $this->em->persist($object);
        $this->em->flush();
        return true;
    }

    public function remove($object){
        $this->em->remove($object);
        $this->em->flush();
        return true;
    }

    private function _getTableBundle($table){
        return $this->bundle . ':' . ucfirst($table);
    }
}
