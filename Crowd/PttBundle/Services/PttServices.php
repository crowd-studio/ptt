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

    private function _sql($table, $lang, $params){
        $sql = '';

        if($lang){
            $sql = '
                SELECT t.*, tm.* FROM ' .$table.' t LEFT JOIN ' .$table.'Trans tm ON t.id = tm.relatedId WHERE tm.language = :lang ';
        } else {
            $sql = '
                SELECT t.* FROM ' .$table.' t WHERE 1=1 ';
        }

        if(isset($params['where'])){
            $sql .= $this->_where($params['where']);    
        }

        if(isset($params['order'])){
            $sql .= 'ORDER BY ';
            foreach ($params['order']) as $key => $order) {
                 $sql .= $order['order'].' '. $order['orderDir'].', ';
             } 

             $sql = trim($sql, ', ');
        }

        if(isset($params['page'])){
            $limit = (isset($params['limit'])) ? $params['limit'] : $this->limit;
            $offset = $page * $limit;
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
                        if($key == 'OR'){ $sql .= ')'; }
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

    public function _get($table, $lang, $params = []){
        $sql = $this->_sql($table, $lang, $params);

        $stmt = $this->em->getConnection()->prepare($sql);
        if($lang){
            $stmt->bindValue('lang', $lang);
        }

        $stmt->execute();
        $data = $stmt->fetchAll();

        $data = $this->prepareObjects($lang, $data, $table, $parameters);

        if($params['one'] && $params['one']){
            $data = (isset($data[0])) ? $data[0] : null;
        }
        return $data;
    }

    public function _getOne($table, $lang, $id, $params){
        $params['where'] = [
            [
                'and' => [
                    ['column' => ($lang) ? 'relatedId' : 'id', 'operator' => '=', 'value' = $id ]
                ]
            ]
        ];
        $params['one'] = true;
        return $this->_get($table, $lang, $params);
    }

    public function _getByPag($table, $lang, $params = []){
        $sql = $this->_sql($table, $lang, $params);

        $sqlLimit = $sql;

        $sql = ', (SELECT COUNT(t.*) FROM' . explode('FROM', $sql, 2)[1] . ') _totalPagCount FROM';

        $sqlArr = explode('FROM', $sqlLimit, 2);
        $sqlLimit = $sqlArr[0] . $sql . $sqlArr[1];

        $stmt = $this->em->getConnection()->prepare($sqlLimit);
        if($lang){
            $stmt->bindValue('lang', $lang);
        }

        $stmt->execute();
        $data = $stmt->fetchAll();

        $total = (isset($data[0])) ? $data[0]["_totalPagCount"] : 0;
        $data = $this->prepareObjects($lang, $data, $table, $parameters);

        $hasNewPages = sizeOf( $total ) / $limit - $page > 1;

        return array('content' => $data, 'hasNewPages' => $hasNewPages, 'size' =>  $limit);
    }

    private function prepareObjects($lang, $element, $table, $parameters = []){
        foreach ($element as $k => $el) {
            //Pdf
            if(isset($el['pdf']) && $el['pdf'] != ''){
                $element[$k]['pdf'] = $this->uploadsUrl . $el['pdf'];
            }

            //Clean
            if(isset($parameters['clean'])){
                $keys = $parameters['clean']);
            } else {
                $keys = array_keys($el);
                unset($keys[array_search('_totalPagCount', $keys)]);
            }

            $element[$k] = $this->CleanObject($el, $keys);

            if(isset($parameters['sizes'])){
                foreach ($parameters['sizes'] as $key => $value) {
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
