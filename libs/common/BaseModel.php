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
    //echo $sqlInsert;
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
     * sql
     * @return string
     */
    public function Sql(){
        return $this->sqlBuilder->sql();
    }

    /**分页查询
     * @param $params
     * @param null $pager
     * @param int $pageSize
     * @param int $pageNo
     * @return array|bool
     * @throws \Exception
     */
    public function GetsPage($params, $pageSize=1,$pageNo=1,$htmlOn=true,$count_fields="*")
    {
        if (empty($params))
        {
            throw new \Exception("no params.");
        }
        $pager=null;
        $params['page']=$pageNo ;
        $selectdb = new SelectDB($this->db);
        $selectdb->from($this->table);
        $selectdb->count_fields=$count_fields;
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