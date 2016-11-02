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
            $ptt = $yaml->parse(file_get_contents(__DIR__ . '/../../../../../../app/config/ptt.yml'));
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
                SELECT t.*, tm.* FROM ' .$table.' t LEFT JOIN ' .$table.'_trans tm ON t.id = tm.relatedId WHERE tm.language = :lang ';
        } else {
            $sql = '
                SELECT t.* FROM ' .$table.' t WHERE 1=1 ';
        }

        if(isset($params['where'])){
            $sql .= $this->_where($params['where']);    
        }

        if(isset($params['order'])){
            $sql .= 'ORDER BY ';
            foreach ($params['order'] as $key => $order) {
                 $sql .= $order['order'].' '. $order['orderDir'].', ';
             } 

             $sql = trim($sql, ', ');
        }

        if(isset($params['page'])){
            $limit = (isset($params['limit'])) ? $params['limit'] : $this->limit;
            $offset = $params['page'] * $limit;
            $sql .= ' LIMIT '. $limit .' OFFSET ' . $offset;
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
                            $sql .= (strtolower($whereLine['operator']) == 'in') ?  '('.$whereLine['value'].') ' : '"'.$whereLine['value'].'" ';
                        }
                    }
                }
                if($key == 'AND'){ $sql .= ') '; }
            }
        }

        return $sql;
    }

    public function get($table, $lang, $params = []){
        $sql = $this->_sql($table, $lang, $params);

        $stmt = $this->em->getConnection()->prepare($sql);
        if($lang){
            $stmt->bindValue('lang', $lang);
        }

        $stmt->execute();
        $data = $stmt->fetchAll();

        $data = $this->_prepareObjects($data, $table, $params);

        if(isset($params['one']) && $params['one']){
            $data = (isset($data[0])) ? $data[0] : null;
        }
        return $data;
    }

    public function getOne($table, $lang, $id, $params = []){
        $params['where'] = [
            [
                'and' => [
                    ['column' => 'id', 'operator' => '=', 'value' => $id ]
                ]
            ]
        ];
        $params['one'] = true;
        return $this->get($table, $lang, $params);
    }

    public function getByPag($table, $lang, $params = []){
        $sql = $this->_sql($table, $lang, $params);

        $sqlLimit = $sql;

        $sql = ', (SELECT COUNT(t.id) FROM' . explode('FROM', explode('ORDER BY', $sql)[0], 2)[1] . ') _totalPagCount FROM';

        $sqlArr = explode('FROM', $sqlLimit, 2);
        $sqlLimit = $sqlArr[0] . $sql . $sqlArr[1];

        $stmt = $this->em->getConnection()->prepare($sqlLimit);
        if($lang){
            $stmt->bindValue('lang', $lang);
        }

        $stmt->execute();
        $data = $stmt->fetchAll();

        $total = (isset($data[0])) ? $data[0]["_totalPagCount"] : 0;
        $data = $this->_prepareObjects($data, $table, $params);
        $limit = (isset($params['limit'])) ? $params['limit'] : $this->limit;
        $hasNewPages = sizeOf( $total ) / $limit - $params['page'] > 1;

        return array('content' => $data, 'hasNewPages' => $hasNewPages, 'size' =>  $limit);
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

    private function _prepareObjects($elements, $table, $parameters = []){
        foreach ($elements as $k => $el) {
            $elements[$k] = $this->_prepareObject($el, $table, $parameters);
        }

        return $elements;
    }

    private function _prepareObject($el, $table, $parameters){
        //Pdf
        if(isset($el['pdf']) && $el['pdf'] != ''){
            $el['pdf'] = $this->uploadsUrl . $el['pdf'];
        }

        //Clean
        if(isset($parameters['clean'])){
            $keys = $parameters['clean'];
        } else {
            $keys = array_keys($el);
            unset($keys[array_search('_totalPagCount', $keys)]);
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
