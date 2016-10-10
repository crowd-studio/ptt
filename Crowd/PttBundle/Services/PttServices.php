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
    protected $em;
    protected $request;
    protected $kernel;
    protected $limit = 6;
    protected $uploadsUrl = '/uploads/';
    protected $model = '';

    public function __construct(\Doctrine\ORM\EntityManager $em, KernelInterface $kernel) {
        $this->em = $em;
        $this->kernel = $kernel;

        try {
            $yaml = new Parser();
            $ptt = $yaml->parse(file_get_contents(BASE_DIR . 'app/config/ptt.yml'));
            $this->uploadsUrl = '/uploads/'; //$ptt['s3']['prodUrl'] . $ptt['s3']['dir'] . '/';
        } catch (ParseException $e) {
            printf("Unable to parse the YAML string: %s", $e->getMessage());
        }

    }

    public function setRequest(RequestStack $request_stack)
    {
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
            foreach ($params['where'] as $whereLine) {
               if ($whereLine['column'] == 'direct-sql-injection'){
                    $sql .= 'AND ' . $whereLine['value'] . ' ';
                } else {
                    $sql .= 'AND t.'.$whereLine['column'].' '.$whereLine['operator'].' ';
                    if($whereLine['operator'] == 'in'){
                        $sql .= '('.$whereLine['value'].') ';
                    }else{
                        $sql .= '"'.$whereLine['value'].'" ';
                    }
                }
            }
        }

        return $sql;
    }



    public function _getAll($table, $lang, $params = array())
    {
        $sql = $this->_sql($table, $lang, $params);

        if(isset($params['order'])){
            $sql .= 'ORDER BY t.'.$params['order'].' '.$params['orderDir'].' ';
        }

        $stmt = $this->em->getConnection()->prepare($sql);
        if($lang){
            $stmt->bindValue('lang', $lang);
        }

        $stmt->execute();
        $data = $stmt->fetchAll();

        for($i=0; $i < count($data); $i++)
        {
            $data[$i]['model'] = $table;
            if(isset($params ['sizes'])){
                $data[$i] = $this->getExtrasGrid($lang, $data[$i],$params['sizes']);
            }else{
                $data[$i] = $this->getExtrasGrid($lang, $data[$i]);
            }

        }

        return $data;
    }

    public function _getAllByPag($table, $page, $lang, $params = array())
    {
        $sql = $this->_sql($table, $lang, $params);
        $sqlLimit = $sql;

        if(isset($params['order'])){
            $sqlLimit .= 'ORDER BY t.'.$params['order'].' '.$params['orderDir'].' ';
        }

        if(!is_bool($page)){
            $offset = $page * $this->limit;
            $sqlLimit .= 'LIMIT '. $this->limit .' OFFSET ' . $offset;
        }

        $stmt = $this->em->getConnection()->prepare($sqlLimit);
        if($lang){
            $stmt->bindValue('lang', $lang);
        }
        $stmt->execute();
        $data = $stmt->fetchAll();

        for($i=0; $i < count($data); $i++)
        {
            $data[$i]['model'] = $table;
            if(isset($params ['sizes'])){
                $data[$i] = $this->getExtrasGrid($lang, $data[$i],$params['sizes']);
            }else{
                $data[$i] = $this->getExtrasGrid($lang, $data[$i]);
            }
        }

        $stmt = $this->em->getConnection()->prepare($sql);
        if($lang){
            $stmt->bindValue('lang', $lang);
        }
        $stmt->execute();
        $total = $stmt->fetchAll();

        $hasNewPages = sizeOf( $total ) / $this->limit - $page > 1;

        return array('content' => $data, 'hasNewPages' => $hasNewPages, 'size' =>  $this->limit);
    }

    public function _getOneByLang($table, $id, $lang, $sizes = false)
    {
        $sql = '
        SELECT t.*, tm.* FROM ' .$table.' t LEFT JOIN ' .$table.'Trans tm
        ON t.id = tm.relatedId WHERE tm.language = :lang AND t.id = :id';

        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->bindValue('lang', $lang);
        $stmt->bindValue('id', $id);
        $stmt->execute();
        $data = $stmt->fetchAll();

        if(isset($data[0])){
            $data[0]['model'] = $table;
            $data[0] = $this->getExtrasGrid($lang, $data[0], $sizes);
            return $data[0];
        } else {
            return null;
        }
    }

    public function _getOneById($table, $id, $sizes = false)
    {
        $sql = '
        SELECT t.* FROM ' .$table.' t WHERE t.id = :id';

        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->bindValue('id', $id);
        $stmt->execute();
        $data = $stmt->fetchAll();

        if(isset($data[0])){
            $data[0]['model'] = $table;
            $data[0] = $this->getExtrasGrid(false, $data[0], $sizes);
            return $data[0];
        } else {
            return null;
        }
    }

    private function getExtrasGrid($lang, $element, $sizes = false){
        //Pdf
        if(isset($element['pdf']) && $element['pdf'] != ''){
            $element['pdf'] = $this->uploadsUrl . $element['pdf'];
        }

        //Images
        if($sizes){
            foreach ($sizes as $key => $value) {
                if(isset($element[$key]) && $element[$key] != '' ){
                    $element[$key] = $this->uploadsUrl . $value . $element[$key];
                }
            }
        }
        

        return $element;
    }

    public function CleanArray($data, $columns)
    {
        if(count($data) > 0){
            foreach ($data as $key => $element) {
                $data[$key] = $this->CleanObject($element, $columns);
            }
        }

        return $data;
    }

    public function CleanObject($data, $columns)
    {

        if(count($data) > 0){

            $col = array();
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
