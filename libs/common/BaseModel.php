<?php
/**
 * PHPProject
 * BaseModel.php Created by usher.yue.
 * User: usher.yue
 * Date: 16/7/5
 * Time: 下午1:05
 * 心怀教育梦－烟台网格软件技术有限公司
 */
namespace App\Model;
use Swoole;
use \SqlBuilder;
use Swoole\SelectDB;
/*
 * @property  $sqlBuilder \SqlBuilder
 */
class BaseModel extends Swoole\Model
{
    public $primary ='id';
    public $foreignkey = 'fid';
    /**sql builder
     * @var \SqlBuilder
     */
    public $sqlBuilder;
    /**
     * @return \SqlBuilder
     */
    public   function sqlBuilder(){
        return $this->sqlBuilder;
    }

    /** 扩展构造函数
     * @param Swoole $swoole
     * @param string $db_key
     */
    function __construct(\Swoole $swoole, $db_key = 'master')
    {
        $this->sqlBuilder=new SqlBuilder();
        parent::__construct($swoole,$db_key);
    }

    /**mulity insert
     * @param $array
     * @param bool|false $delay 是否延迟插入
     */
    function Puts($array) {
        if($array==null||!is_array($array)){
            return false ;
        }
        $insertValues=null;
        $fields='';
        foreach($array as &$item){
            //对每个关键字进行排序
            ksort($item);
            if($fields==''){
                $fields=implode(',',array_keys($item));
            }
            foreach($item as $k=>&$v){
                $v='\''.$v.'\'';
            }
            $insertValues[]='('.implode(',',$item).')';
        }
        $sqlInsert=sprintf("insert  into `$this->table` ($fields) VALUES %s",implode(',',$insertValues));
      // echo $sqlInsert;
        return $this->db->query($sqlInsert);
    }

    /**通过表达式进行update是针对update的一个扩展
     * @param $data
     * @param $prm
     */
    function  SetWithExpr($data,$prms){
        $sql="update {$this->table} set " ;
        //拼接expr
        if(array_key_exists('expr',$data)){
            $sql.=$data['expr'];
            unset($data['expr']);
        }
        $arrSetExpr=[];
        foreach($data as $k=>$v){
            $arrSetExpr[]="$k='$v'";
        }
        $condition=implode(',',$arrSetExpr);
        $sql=($condition==''?$sql:$sql.','.$condition);
        $arrSetExpr=[];
        foreach($prms as $k=>$v){
            $arrSetExpr[]="$k='$v'";
        }
        //var_dump($prms);
        $condition=implode(' and ',$arrSetExpr);
        $sql=($condition==''?$sql:$sql.'  where '.$condition);

        return $this->db->query($sql);

    }


    /**直接根据表名字创建 BaseModel
     * @param $table_name
     * @param string $db_key  master slave
     * @return BaseModel
     */
    static function Table($table_name,$db_key='master'){
        $model = new BaseModel(Swoole::getInstance()->model->swoole, $db_key);
        $model->table = $table_name;
        return $model;
    }

    /**执行SQL
     * @param $sql
     * @return Swoole\Database\MySQLiRecord
     */
    public  function  ExecuteSQL($sql){
        return $this->db->query($sql);
    }

    /**
     * @param array $fields
     * @return $this
     */
    public function Select($fields=array()){
        $this->sqlBuilder->select($fields);
        return $this ;
    }

    /**
     * @param $fields
     * @param string $order
     */
    public  function OrderBy($fields,$order='desc'){
        $this->sqlBuilder->orderby($fields,$order);
        return $this;
    }

    /**
     * @param array $tables
     * @return $this
     */
    public function  From($tables=""){
        if(is_string($tables)){
            if($tables=="")
                $this->sqlBuilder->from($this->table);
            else
                $this->sqlBuilder->from($tables);
        }else if(is_array($tables)){
            $this->sqlBuilder->from($tables);
        }

        return $this ;
    }

    /**配合表达式
     * @param array $conditionExpr
     * @return $this
     */
    public function Wheres($conditionExpr=array()){
        //拼接expr
        $this->sqlBuilder->wheres($conditionExpr);
        return $this ;
    }

    /**
     * @param $conditon
     * @return $this
     */
    public function Where($conditon){
        $this->sqlBuilder->where($conditon);
        return $this ;
    }

    /**
     * @param $field
     * @return $this
     */
    public  function And_($field){
        $this->sqlBuilder->and_($field);
        return $this ;
    }

    /**
     * @param $arr
     */
    public  function In($arr){
        $this->sqlBuilder->in($arr);
        return $this ;
    }


    /**
     * @param $conditon
     * @return $this
     */
    public function WhereAnd($condition){
        $this->sqlBuilder->whereAnd($condition);
        return $this ;
    }


    /**
     * @param $conditon
     * @return $this
     */
    public function  WhereOr($conditon){
        $this->sqlBuilder->whereOr($conditon);
        return $this ;
    }


    /**
     * @param $condition
     * @return $this
     */
    public function  On($condition){
        $this->sqlBuilder->on($condition);
        return $this ;
    }

    /**
     * @param $tablename
     * @param string $join_type
     * @return $this
     */
    public function Join($tablename,$join_type='join'){
        $this->sqlBuilder->join($tablename,$join_type);
        return $this ;
    }

    /**
     * @param $condition
     * @return $this
     */
    public function OnAnd($condition){
        $this->sqlBuilder->onAnd($condition);
        return $this ;
    }

    /**
     * @param $condition
     * @return $this
     */
    public function OnOr($condition){
        $this->sqlBuilder->onOr($condition);
        return $this ;
    }

    /**
     * @param array $tables
     * @return $this
     */
    public function Update($tables){
        $this->sqlBuilder->update($tables);
        return $this ;
    }

    /**
     * @param array $conditionExpr
     * @return $this
     */
    public function  Set($conditionExpr=array()){
        $this->sqlBuilder->set($conditionExpr);
        return $this ;
    }

    /**
     * @param array $fields
     * @param array $values
     */
    public function Values($values=[[]]){
        $this->sqlBuilder->values($values);
        return $this ;
    }


    public function InsertInto($table,$fields=[]){
        $this->sqlBuilder->insertinto($table,$fields);
        return $this ;
    }

    /**
     * @param $num
     * @param int $offset
     * @return $this
     */
    public function Limit($num,$offset=0){
        if($offset<=0)
             $this->sqlBuilder->limit($num);
        else
            $this->sqlBuilder->limit($num,$offset);
        return $this ;
    }

    /**
     * sql
     * @return string
     */
    public function Sql(){
        return $this->sqlBuilder->sql();
    }

    /**
     * 执行连贯操作
     */
    public function Exec(){
        return $this->ExecuteSQL($this->sqlBuilder->sql());
    }

    /**分页查询
     * @param $params
     * @param null $pager
     * @param int $pageSize
     * @param int $pageNo
     * @return array|bool
     * @throws \Exception
     */
    public function GetsPage($params, $pageSize=1,$pageNo=1,$htmlOn=true)
    {
        if (empty($params))
        {
            throw new \Exception("no params.");
        }
        $pager=null;
        $params['page']=$pageNo ;
        $selectdb = new SelectDB($this->db);
        $selectdb->from($this->table);
        $selectdb->count_fields="*";
        $selectdb->primary = $this->primary;
        $selectdb->select($this->select);
        $selectdb->page_size=$pageSize;
        //如果没有设置order 默认主键排序
        if (!isset($params['order']))
        {
            $params['order'] = "`{$this->table}`.{$this->primary} desc";
        }
        $selectdb->put($params);
        if (isset($params['page']))
        {
            $selectdb->paging();
            $pager = $selectdb->pager;
        }
        $result=$selectdb->getall();
        return [
            'list'=>$result,
            'totle'=>$pager->total,
            'totlepage'=>$pager->totalpage,
            'page'=>$htmlOn?$pager->render():"",
            'pagesize'=>$pager->pagesize,
            'current'=>$pager->page
        ];
    }





}