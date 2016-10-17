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

    public function __construct(\Doctrine\ORM\EntityManager $em, KernelInterface $kernel) {
        $this->em = $em;
        $this->kernel = $kernel;
        
        try {
            $yaml = new Parser();
            $ptt = $yaml->parse(file_get_contents(BASE_DIR . 'app/config/ptt.yml'));
            $this->uploadsUrl = (isset($ptt['s3']['force']) && $ptt['s3']['force']) ? $ptt['s3']['prodUrl'] . $ptt['s3']['dir'] . '/' : '/uploads/';
        } catch (ParseException $e) {
            printf("Unable to parse the YAML string: %s", $e->getMessage());
        }
    }

    public function setRequest(RequestStack $request_stack){
        $this->request = $request_stack->getCurrentRequest();
    }

    public function get($table, $params = []){
        $sql = $this->_sql($table, $params);

        $stmt = $this->em->getConnection()->prepare($sql);
        if(isset($params['lang'])){
            $stmt->bindValue('lang', $params['lang']);
        }

        $stmt->execute();
        $data = $stmt->fetchAll();

        $data = $this->prepareObjects($data, $table, $params);

        if(isset($params['one']) && $params['one']){
            $data = (isset($data[0])) ? $data[0] : null;
        }
        return $data;
    }

    public function getOne($table, $id, $params = []){
        $params['where'] = [
            [
                'and' => [
                    ['column' => (isset($params['lang'])) ? 'relatedId' : 'id', 'operator' => '=', 'value' => $id ]
                ]
            ]
        ];
        $params['one'] = true;
        return $this->get($table, $params);
    }

    public function getByPag($table, $params = []){
        $sql = $this->_sql($table, $params);

        $sqlLimit = $sql;

        $sql = ', (SELECT COUNT(*) FROM' . explode('FROM', explode('LIMIT', $sql)[0], 2)[1] . ') _totalPagCount FROM';

        $sqlArr = explode('FROM', $sqlLimit, 2);
        $sqlLimit = $sqlArr[0] . $sql . $sqlArr[1];

        $stmt = $this->em->getConnection()->prepare($sqlLimit);
        if(isset($params['lang'])){
            $stmt->bindValue('lang', $params['lang']);
        }

        $stmt->execute();
        $data = $stmt->fetchAll();

        $total = (isset($data[0])) ? $data[0]["_totalPagCount"] : 0;
        $data = $this->prepareObjects($data, $table, $params);

        $limit = (isset($params['limit'])) ? $params['limit'] : $this->limit;

        $hasNewPages = sizeOf( $total ) / $limit - $params['page'] > 1;

        return array('content' => $data, 'hasNewPages' => $hasNewPages, 'size' =>  $limit);
    }

    public function execSQL($sql, $bind = [], $params = []){
        $stmt = $this->em->getConnection()->prepare($sql);

        foreach ($bind as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $data = $stmt->fetchAll();
        $data = $this->prepareObjects($data, (isset($params['table'])) ? $params['table'] : '', $params);

        return $data;
    }

    private function _sql($table, $params){
        $sql = '';

        if(isset($params['lang'])){
            $sql = '
                SELECT t.*, tm.* FROM ' .$table.' t LEFT JOIN ' .$table.'Trans tm ON t.id = tm.relatedId WHERE tm.language = :lang ';
        } else {
            $sql = '
                SELECT t.* FROM ' .$table.' t WHERE 1=1 ';
        }

        if(isset($params['where'])){
            $sql .= $this->_where($params['where']);    
        }

        $sql = str_replace('WHERE 1=1 OR', 'WHERE ', $sql);   
        $sql = str_replace('WHERE 1=1 AND', 'WHERE ', $sql);   

        if(isset($params['order'])){
            $orderArr = [];
            foreach ($params['order'] as $key => $order) {
                 $orderArr[] = $key.' '. $order;
             } 

             $sql .= 'ORDER BY ' . implode(', ', $orderArr) . ' ';
        }

        if(isset($params['page'])){
            $limit = (isset($params['limit'])) ? $params['limit'] : $this->limit;
            $offset = $params['page'] * $limit;
            $sql .= 'LIMIT '. $limit .' OFFSET ' . $offset;
        }

        return $sql;
    }

    private function _where($where, $sql = ''){

        foreach ($where  as $line) {
            foreach ($line as $key => $lines) {
                $key = strtoupper($key);
                if($key == 'AND'){ $sql .= 'AND (1=1 '; }
                foreach($lines as $whereLine){
                    if(is_array($whereLine['column'])){
                        if($key == 'OR'){ $sql .= 'OR (1=1 '; }
                        $sql = $this->_where($whereLine['column'], $sql);
                        if($key == 'OR'){ $sql .= ') '; }
                    } else {
                        if ($whereLine['column'] == 'direct-sql-injection'){
                            $sql .= $key . ' ' . $whereLine['value'] . ' ';
                        } else {
                            $sql .= $key . ' t.'.$whereLine['column'].' '.$whereLine['operator'].' ';
                            $sql .= ($whereLine['operator'] == 'in') ?  '('.$whereLine['value'].') ' : '"'.$whereLine['value'].'" ';
                        }
                    }
                }
                if($key == 'AND'){ $sql .= ') '; }
            }
        }

        return $sql;
    }

    private function prepareObjects($element, $table, $params = []){
        foreach ($element as $k => $el) {
            //Pdf
            if(isset($el['pdf']) && $el['pdf'] != ''){
                $element[$k]['pdf'] = $this->uploadsUrl . $el['pdf'];
            }

            //Clean
            if(isset($params['clean'])){
                $keys = $params['clean'];
            } else {
                $keys = array_keys($el);
                unset($keys[array_search('_totalPagCount', $keys)]);
            }

            $element[$k] = $this->CleanObject($el, $keys);

            if(isset($params['sizes'])){
                foreach ($params['sizes'] as $key => $value) {
                    if(isset($el[$key]) && $el[$key] != '' ){
                        $element[$k][$key] = $this->uploadsUrl . $value . $el[$key];
                    }
                }
            }

            $element[$k]['_model'] = $table;
        }

        return $element;
    }

    private function CleanObject($data, $columns){
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
