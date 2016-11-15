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
        $orderCol = [];
        if(isset($params['order'])){
            foreach ($params['order'] as $key => $order) {
                $qb->orderBy('t.' . $order['order'], $order['orderDir']);
                $orderCol[] = 't.' . $order['order'];
             } 
        } else {
            $col = $this->em->getClassMetadata($tableBundle)->getFieldNames();
            if(array_search('_order', $col)){
                $qb->orderBy('t._order');
                $orderCol[] ='t._order';
            }
        }

        $qb->select(array_merge(['t'], $orderCol))->from($tableBundle, 't');

        if(isset($params['where'])){ 
            $qb = $this->_where($params['where'], $qb);
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
        
        if(isset($params['one']) && $params['one']){    
            $data = $query->getSingleResult();
            $data = $this->_prepareObject($data, $params);
        } else {
            $data = $query->getResult();
            $data = $this->_prepareObjects($data, $params);
        }
        
        return $data;
    }

    public function getSimpleFilter($table, $params = []){

        $where = (isset($params['where'])) ? $params['where'] : [];
        $orderBy = (isset($params['orderBy'])) ? $params['orderBy'] : [];

        $obj = $this->em->getRepository($this->_getTableBundle($table))
        ->findBy($where, $orderBy);
    }

    public function getAll($table){
        $obj = $this->em->getRepository($this->_getTableBundle($table))
        ->findAll();
        $obj = $this->_prepareObjects($obj, $params);
        return $obj;
    }

    public function getOne($table, $id, $params = []){
        $obj = $this->em->getRepository($this->bundle . ':' . ucfirst($table))->find($id);

        $obj = $this->_prepareObject($obj, $params);
        return $obj;

    }

    public function getByPag($table, $lang, $params = []){
        $page = (isset($params['page'])) ? $params['page'] : 0;
        $limit = (isset($params['limit'])) ? $params['limit'] : $this->limit;

        unset($params['page']); unset($params['limit']);

        $total = $this->get($table, $lang, $params);

        $params['page'] = $page;
        $params['limit'] = $limit;
        $data = $this->get($table, $lang, $params);
        
        $hasNewPages = count($total) / $limit - $page > 1;

        return ['content' => $data, 'hasNewPages' => $hasNewPages, 'size' =>  $limit];
    }

    public function getModules($id, $model, $lang, $params = []){
        $moduleSQL = [];
        foreach ($params as $module => $mod) {
            $moduleSQL[] = 'SELECT id, "' . $module . '" as type, _order FROM ' . $module . ' WHERE related_id = :relid AND _model = :model';
        }

        $sql = implode(' UNION ALL ', $moduleSQL);

        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->bindValue('relid', $id);
        $stmt->bindValue('model', $model);
        $stmt->execute();
        $array = $stmt->fetchAll();
        $size = count($array);
        
        $modules = [];
        if($size){
            foreach ($array as $key => $row)
            {
                $order[$key] = $row['_order'];
            }

            array_multisort($order, SORT_ASC, $array);   

            for($i=0; $i<$size; $i++)
            {
                if($params[$array[$i]['type']]['trans']){
                    $sql = '
                    SELECT a.*, at.*, "'.$array[$i]['type'].'" as type
                    FROM '. $array[$i]['type'] .' a LEFT JOIN '.$array[$i]['type'].'_trans at ON a.id = at.relatedId
                    WHERE a.ID = :id AND at.language = :lang';

                    $stmt = $this->em->getConnection()->prepare($sql);
                    $stmt->bindValue('id', $array[$i]['id']);
                    $stmt->bindValue('lang', $lang);
                
                } else {
                    $sql = '
                    SELECT a.*, "'.$array[$i]['type'].'" as type
                    FROM '. $array[$i]['type'] .' a 
                    WHERE a.ID = :id ';

                    $stmt = $this->em->getConnection()->prepare($sql);
                    $stmt->bindValue('id', $array[$i]['id']);
                }
                

                $stmt->execute();
                $aux = $stmt->fetchAll();

                foreach ($aux as $key => $module) {
                    $modules[] = $this->_prepareObject($module, $module['type'], $params[$module['type']]);
                }
                
            }
        }

        return $modules;
    }

    private function _prepareObjects($elements, $parameters = [], $father = false){

        foreach ($elements as $k => $el) {
            $elements[$k] = $this->_prepareObject($el, $parameters, $father);
        }

        return $elements;
    }

    private function _prepareObject($el, $parameters, $father = false){
        //Pdf
        if(method_exists($el, 'getPdf') && $el->getPdf() != ''){
            $el->setPdf($this->uploadsUrl . $el->getPdf());
        }

        // Images
        if(isset($parameters['sizes'])){
            foreach ($parameters['sizes'] as $key => $value) {
                $getMethod = 'get' . ucfirst($key);
                $setMethod = 'set' . ucfirst($key);
                if(method_exists($el, $setMethod) && $el->$getMethod() != ''){
                    $el->$setMethod($this->uploadsUrl . $value . $el->$getMethod());
                }
            }
        }
        
        $object = new \ReflectionObject($el);
        foreach ($object->getMethods() as $method) {
            if(substr($method, 0, 3) === "get"){
                $setMethod = 's' . substr($method, 1);
                if(is_array($el->$method())){
                    $el->$setMethod($_prepareObjects($el->$method(), $parameters));
                } elseif (is_object($el->$method())) {
                    $el->$setMethod($_prepareObject($el->$method(), $parameters));
                }
            }
        }

        return $el;
    }

    private function _getTableBundle($table){
        return $this->bundle . ':' . ucfirst($table);
    }
}
