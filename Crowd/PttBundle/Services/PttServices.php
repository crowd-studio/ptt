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
        
        $this->bundle = PttUtil::pttConfiguration('bundles')[0]['bundle'];
    }

    public function setRequest(RequestStack $request_stack){
        $this->request = $request_stack->getCurrentRequest();
    }

    private function _sql($table, $params){

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

    public function get($table, $params = []){
        $qb = $this->_sql($table, $params);
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

    public function getByPag($table, $params = []){
        $page = (isset($params['page'])) ? $params['page'] - 1 : 0;
        $limit = (isset($params['limit'])) ? $params['limit'] : $this->limit;
        $name = (isset($params['name'])) ? $params['name'] : '';
        $qb = $this->_sql($table, $params);
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
        
        return [
            'content' => $data, 
            'pagination' => [
                'thisPage' => $page + 1,
                'maxPages' => $maxPages,
                'hasNewPages' => $hasNewPages,
                'name' => $name
            ]
        ];
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
        $this->_deleteCache();
        return true;
    }

    public function create($object){
        $this->em->persist($object);
        $this->em->flush();

        $this->_deleteCache();
        return true;
    }



    public function remove($object){
        $this->em->remove($object);
        $this->em->flush();

        $this->_deleteCache();
        return true;
    }

    private function _deleteCache(){
        $cache = new PttCache();
        $cache->removeAll();
    }

    private function _getTableBundle($table){
        return $this->bundle . ':' . ucfirst($table);
    }
}
