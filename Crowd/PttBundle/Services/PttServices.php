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

        if(is_array($data)){
            $data = $this->_parseObjects($data, $params);
        } else {
            $data = $this->_parseObjects([$data], $params)[0];
        }

        if (isset($params['json']) && $params['json']) {
            $data = json_encode($data);
        }

        return $data;
    }

    public function getSimpleFilter($table, $params = []){
        $where = (isset($params['where'])) ? $params['where'] : [];
        $orderBy = (isset($params['orderBy'])) ? $params['orderBy'] : [];

        $data = $this->em->getRepository($this->_getTableBundle($table))->findBy($where, $orderBy);
        return $this->_parseObjects($data, $params);
    }

    public function getAll($table, $params = []){
        $data = $this->em->getRepository($this->_getTableBundle($table))->findAll();
        return $this->_parseObjects($data, $params);
    }

    public function getOne($table, $id, $params = []){
        $data = $this->em->getRepository($this->_getTableBundle($table))->find($id);
        return ($data) ? $this->_parseObjects([$data], $params)[0] : null;
    }

    public function getByPag($table, $params = []){
        $page = (isset($params['page'])) ? $params['page'] : 0;
        $limit = (isset($params['limit'])) ? $params['limit'] : $this->limit;

        $qb = $this->_sql($table, $params);
        $query = $qb->getQuery();

        if($limit > 0){
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

        } else {
            $data = $query->getResult();
            $maxPages = 1;
            $hasNewPages = false;
        }

        $data = $this->_parseObjects($data, $params);


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

    private function _parseObjects($array, $params){
        foreach ($array as $key => $obj) {
            $obj = (isset($params['language'])) ? $this->_parseLanguage($obj, $params['language']) : $obj;
            $obj = (isset($params['modules'])) ? $this->_parseModules($obj, $params) : $obj;
            $obj = (isset($params['json']) && $params['json']) ? $this->_parseJSON($obj) : $obj;

            $array[$key] = $obj;
        }

        return $array;
    }

    private function _parseLanguage($obj, $lang){
        if(method_exists($obj, 'getTrans')){
            foreach ($obj->getTrans() as $key => $value) {
                if($value->getLanguage()->getCode() == $lang){
                    $obj->setATrans($value);
                }
            }
        }

        return $obj;
    }

    private function _parseModules($obj, $params){
        if(method_exists($obj, 'getModules')){
            $id = $obj->getId();
            $name = $this->get_class_name($obj);
            $mods = [];
            foreach ($params['modules'] as $key => $module) {
                $data = $this->em->getRepository($this->_getTableBundle($module))->findBy(['relatedid' => $id, '_model' => $name], ['_order' => 'ASC']);
                $mods = array_merge($mods, $data);
            }

            $mods = $this->_parseObjects($mods, $params);

            foreach ($mods as $key => $row){
                $order[$key] = $row->get_Order();
            }

            array_multisort($order, SORT_ASC, $mods);
            $obj->setModules($mods);
        }

        return $obj;
    }

    private function _parseJSON($obj){
        return (method_exists($obj, 'getJSON')) ? $obj->getJson() : $obj;
    }

    private function _getTableBundle($table){
        return $this->bundle . ':' . ucfirst($table);
    }

    private function get_class_name($obj)
    {
        $classname = get_class($obj);
        if ($pos = strrpos($classname, '\\')) return substr($classname, $pos + 1);
        return $pos;
    }

    private function _deleteCache(){
        $pttCache = new PttCache();
        $pttCache->removeAll();
    }
}
