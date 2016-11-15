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
        $tableBundle = $this->bundle . ':' . ucfirst($table);
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

        if($lang){
            $arrayTable = ['t', 'tm'];
            $qb->leftJoin($tableBundle . 'Trans', 'tm', 'WITH', 't.id = tm.relatedId')
                ->andWhere("tm.language = '" . $lang . "'");
        } else {
            $arrayTable = ['t'];
        }

        $qb->select(array_merge($arrayTable, $orderCol))->from($tableBundle, 't');

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
        
        $data = $query->getArrayResult();
        if(isset($params['one']) && $params['one']){    
            if(!$lang){
                $data = isset($data[0]) ? $data[0] : $data;
            }

            $data = $this->_prepareObject($data, $table, $lang, $params);
        } else {
            $data = $this->_prepareObjects($data, $table, $lang, $params);
        }
        
        return $data;
    }

    public function getOne($table, $lang, $id, $params = []){
        $tableBundle = $this->bundle . ':' . ucfirst($table);
        $obj = $this->em->getRepository($tableBundle)
        ->find($id);
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

    private function _prepareObjects($elements, $table, $lang, $parameters = []){
        if($lang){
            $elements = $this->_mergeArray($elements);
        }

        foreach ($elements as $k => $el) {
            $elements[$k] = $this->_prepareObject($el, $table, false, $parameters);
        }

        return $elements;
    }

    private function _prepareObject($el, $table, $lang, $parameters){
        if($lang){
            $el = $this->_mergeArray($el)[0];
        }

        //Pdf
        if(isset($el['pdf']) && $el['pdf'] != ''){
            $el['pdf'] = $this->uploadsUrl . $el['pdf'];
        }
        //Clean
        if(isset($parameters['clean'])){
            $keys = $parameters['clean'];
        } else {
            $keys = array_keys($el);
        }

        $el = $this->_cleanObject($el, $keys);

        // Images
        if(isset($parameters['sizes'])){
            foreach ($parameters['sizes'] as $key => $value) {
                if(isset($el[$key]) && $el[$key] != '' ){
                    $el[$key] = $this->uploadsUrl . $value . $el[$key];
                }
            }
        }
        
        // Model
        $el['_model'] = $table;

        return $el;
    }

    private function _mergeArray($elements){
        $final = [];
        for ($i=0; $i < count($elements); $i++) { 
            $base = $elements[$i];
            $i++;
            $trans = $elements[$i];

            if(isset($base['title'])) { unset($base['title']);}
            if(isset($base['slug'])) { unset($base['slug']);}
            unset($trans['relatedId']);
            unset($trans['id']);
            $final[] = array_merge($base, $trans);
        }

        return $final;
    }

    private function _cleanObject($data, $columns){
        if(count($data) > 0){
            $col = [];
            foreach ($columns as $column) {
                if (isset($data[$column])){
                    $col[$column] = $data[$column];
                }
            }
            return $col;
        } else {
            return $data;
        }

    }
}
