<?php

namespace App\FrontendBundle\Services;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Crowd\PttBundle\Util\PttCache;
use Crowd\PttBundle\Util\PttUtil;

use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;

use Symfony\Component\Security\Core\Util\SecureRandom;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;

class ColectaniaServices
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

    protected function _getAll($table, $params = false)
    {
        $sql = '
        SELECT t.* FROM ' .$table.' t ';

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

        if(isset($params['order'])){
            $sql .= 'ORDER BY t.'.$params['order'].' '.$params['orderDir'].' ';
        }


        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll();

        for($i=0; $i < count($data); $i++)
        {
            $data[$i] = $this->getExtrasGrid(false,$data[$i]);
        }

        return $data;
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

        if(isset($params['nopublic'])){
        }
        elseif(isset($params['public'])){
            $sql .= 'AND t.public = '.$params['public'].' ';
        }else{
            $sql .= 'AND t.public = 1 ';
        }

        return $sql;
    }



    protected function _getAllClean($table, $lang, $params = array())
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

    protected function _getAllByPag($table, $page, $lang, $params = array())
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

    protected function _getOneByLang($table, $id, $lang, $sizes = ['sizes' => ['image'=>'1400-600-']])
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
            $data[0] = $this->getExtrasGrid($lang, $data[0], $sizes['sizes']);
            return $data[0];
        } else {
            return null;
        }
    }

    protected function _getOneById($table, $id, $sizes = ['sizes' => ['image'=>'640-470-']])
    {
        $sql = '
        SELECT t.* FROM ' .$table.' t WHERE t.id = :id';

        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->bindValue('id', $id);
        $stmt->execute();
        $data = $stmt->fetchAll();

        if(isset($data[0])){
            $data[0]['model'] = $table;
            $data[0] = $this->getExtrasGrid(false, $data[0], $sizes['sizes']);
            return $data[0];
        } else {
            return null;
        }
    }

    protected function _getAllSearch($text, $lang, $tables = [])
    {

        foreach ($tables as $key => $table) {
            $name = $key;

            if ($table['public']){
                $public = ['public' => 1];
            } else {
                $public = ['nopublic' => true];
            }

            $sql = $this->_sql($key, $lang, $public);   

            $sql .= ' AND (';
            $or = '';
            foreach ($table['columns'] as $key => $col) {
                $or .= ' OR ' . $col . ' LIKE "%' . $text . '%"';
            }

            $or = trim($or, " OR");
            $sql .= $or . ') ORDER BY t.' . $table['order'] . ' LIMIT 9';

            $stmt = $this->em->getConnection()->prepare($sql);
            if($lang){
                $stmt->bindValue('lang', $lang);    
            }

            $stmt->execute();
            $data = $stmt->fetchAll();

            foreach ($data as $r => $row) {
                $data[$r]['model'] = $name;

                if($name == 'Activity' || $name == 'Exhibition'){
                    $data[$r] = $this->getExtrasGrid($lang, $data[$r], ['image' => '1400-600-','thumbnail' => '600-470-']);
                } else {
                    $data[$r] = $this->getExtrasGrid($lang, $data[$r]);    
                }
                
            }

            $tables[$name] = $data;


        }

        // Obtenim els originals relacionats amb el mòdul
        foreach ($tables["moduleBody"] as $key => $module) {
            if(array_key_exists($module["_model"], $tables)){
                $aux = $this->_getOneByLang($module["_model"], $module["related_id"], $lang, ['sizes' => ['image'=>'600-470-']]);
                $tables[$module["_model"]][] = $aux;
            }
        }

        unset($tables["moduleBody"]);
        
        // Eliminar repes
        foreach ($tables as $key => $value) {
            $tables[$key] = array_values(array_map("unserialize", array_unique(array_map("serialize", $value))));
        }
        
        

        return $tables;
    }

    private function _getTag($id, $lang)
    {
        $sql = '
        SELECT t.id, tt.title, tt.slug FROM Tag t LEFT JOIN TagTrans tt ON t.id = tt.relatedId WHERE t.id = :id AND tt.language = :lang';

        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->bindValue('id', $id);
        $stmt->bindValue('lang', $lang);
        $stmt->execute();
        $data = $stmt->fetchAll();

        if(isset($data[0])){
            return $data;
        } else {
            return array();
        }

    }

    private function _getTags($id, $lang, $model)
    {
        switch($model){
            case 'Exhibition':$table = $model;break;
            case 'Workshop':
            case 'Activity': $table = 'Activity';break;

        }
        $sql = '
        SELECT t.id, tt.title, tt.slug FROM Tag t LEFT JOIN TagTrans tt ON t.id = tt.relatedId
        WHERE t.id in (SELECT tagId from '.$table.'Tag WHERE '.strtolower ($table).'Id = :id) AND tt.language = :lang';

        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->bindValue('id', $id);
        $stmt->bindValue('lang', $lang);
        $stmt->execute();
        $data = $stmt->fetchAll();

        return $data;
    }

    protected function getExtrasGrid($lang, $element, $sizes = false){
        //Pdf
        if(isset($element['pdf']) && $element['pdf'] != ''){
            $element['pdf'] = $this->uploadsUrl . $element['pdf'];
        }

        //Images
        foreach ($sizes as $key => $value) {
            if(isset($element[$key]) && $element[$key] != '' ){
                $element[$key] = $this->uploadsUrl . $value . $element[$key];
            }

        }

        return $element;
    }

    protected function CleanArray($data, $columns)
    {
        if(count($data) > 0){
            foreach ($data as $key => $element) {
                $data[$key] = $this->CleanObject($element, $columns);
            }
        }

        return $data;
    }

    protected function CleanObject($data, $columns)
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
