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
    public $primary = 'id';
    public $foreignkey = 'fid';
    /**sql builder
     * @var \SqlBuilder
     */
    public $sqlBuilder;

    /**
     * @return \SqlBuilder
     */
    public function sqlBuilder()
    {
        return $this->sqlBuilder;
    }

    /** 扩展构造函数
     * @param Swoole $swoole
     * @param string $db_key
     */
    function __construct(\Swoole $swoole, $db_key = 'master')
    {
        $this->sqlBuilder = new SqlBuilder();
        parent::__construct($swoole, $db_key);
    }

    /**mulity insert
     * @param $array
     * @param bool|false $delay 是否延迟插入
     */
    function Puts($array)
    {
        if ($array == null || !is_array($array)) {
            return false;
        }
        $insertValues = null;
        $fields = '';
        foreach ($array as &$item) {
            //对每个关键字进行排序
            ksort($item);
            if ($fields == '') {
                $fields = implode(',', array_keys($item));
            }
            foreach ($item as $k => &$v) {
                $v = '\'' . $v . '\'';
            }
            $insertValues[] = '(' . implode(',', $item) . ')';
        }
        $sqlInsert = sprintf("insert  into `$this->table` ($fields) VALUES %s", implode(',', $insertValues));
        //echo $sqlInsert;
        return $this->db->query($sqlInsert);
    }

    /**通过表达式进行update是针对update的一个扩展 支持不同情况
     * @param $data
     * @param $prm
     */
    function  SetWithExpr($data, $prms)
    {
        $sql = "update {$this->table} set ";
        if (is_string($data)) {
            $sql .= $data;
        } elseif (is_array($data)) {
            //拼接expr数组情况下
            if (array_key_exists('expr', $data)) {
                $sql .= $data['expr'];
                unset($data['expr']);
            }
            $arrSetExpr = [];
            //判断是关联数组还是索引数组
            if (is_assoc($data)) {
                foreach ($data as $k => $v) {
                    $arrSetExpr[] = "$k='$v'";
                }
            } else {
                foreach ($data as $v) {
                    $arrSetExpr[] = $v;
                }
            }
            $condition = implode(',', $arrSetExpr);
            $sql = ($condition == '' ? $sql : $sql . $condition);
        }
        if (is_string($prms)) {
            $sql .= " where $prms";
        } elseif (is_array($prms)) {
            $arrSetExpr = [];
            //判断是关联数组还是索引数组
            if (is_assoc($prms)) {
                foreach ($prms as $k => $v) {
                    $arrSetExpr[] = "$k='$v'";
                }
            } else {
                foreach ($prms as $v) {
                    $arrSetExpr[] = $v;
                }
            }
            //var_dump($prms);
            $condition = implode(' and ', $arrSetExpr);
            $sql = ($condition == '' ? $sql : $sql . '  where ' . $condition);
        }
        return $this->db->query($sql);

    }


    /**直接根据表名字创建 BaseModel
     * @param $table_name
     * @param string $db_key master slave
     * @return BaseModel
     */
    static function Table($table_name, $db_key = 'master')
    {
        $model = new BaseModel(Swoole::getInstance()->model->swoole, $db_key);
        $model->table = $table_name;
        return $model;
    }

    /**执行SQL
     * @param $sql
     * @return Swoole\Database\MySQLiRecord
     */
    public function  ExecuteSQL($sql)
    {
        return $this->db->query($sql);
    }


    /**根据字段获取
     * @param $fields
     * @param $id
     * @param string $where
     * @return array
     * @throws \Exception
     */
    public function GetFieldByID($fields, $id, $where = 'id', $limit = '')
    {
        if ($limit == '') {
            $result = $this->gets([
                'select' => $fields,
                $where => $id,
            ]);
        } else {
            $result = $this->gets([
                'select' => $fields,
                $where => $id,
                'limit' => $limit
            ]);
        }
        return empty($result) ? $result : $result[0];
    }


    /**
     * sql
     * @return string
     */
    public function Sql()
    {
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
    public function GetsPage($params, $pageSize = 1, $pageNo = 1, $htmlOn = true, $count_fields = "*")
    {
        if (empty($params)) {
            throw new \Exception("no params.");
        }
        $pager = null;
        $params['page'] = $pageNo;
        $selectdb = new SelectDB($this->db);
        $selectdb->from($this->table);
        $selectdb->count_fields = $count_fields;
        $selectdb->primary = $this->primary;
        $selectdb->select($this->select);
        $selectdb->page_size = $pageSize;
        //如果没有设置order 默认主键排序 默认是id 如果需要可自己设置order
//        if (!isset($params['order']))
//        {
//            $params['order'] = "`{$this->table}`.{$this->primary} desc";
//        }
        $selectdb->put($params);
        if (isset($params['page'])) {
            $selectdb->paging();
            $pager = $selectdb->pager;
        }
        $result = $selectdb->getall();
        return [
            'list' => $result,
            'total' => $pager->total,
            'totalpage' => $pager->totalpage,
            'page' => $htmlOn ? $pager->render() : "",
            'pagesize' => $pager->pagesize,
            'current' => $pager->page
        ];
    }

    /**内存分页
     * @param $allCount
     * @param $data
     * @param $page
     * @param $pagesize
     * @return array
     */
     public  function  GetsMemPage($allCount,$data,$page,$pagesize){
         if($allCount==0){
             return [
                 'pagesize'=>$pagesize,
                 'total'=>0,
                 'current'=>$page,
                 'totalpage'=>0,
                 'list'=>[]
             ];
         }elseif($allCount>0&&$allCount<=$pagesize){
             //不够一页
             if($page==1){
                 return [
                     'pagesize'=>$pagesize,
                     'total'=>$allCount,
                     'totalpage'=>1,
                     'current'=>1,
                     'list'=>$data
                 ];
             }
             else{
                 return [
                     'pagesize'=>$pagesize,
                     'total'=>$allCount,
                     'totalpage'=>1,
                     'current'=>$page,
                     'list'=>[]
                 ];
             }
         }elseif($allCount>$pagesize){
             //大于1页
             $totalPage=($allCount%$pagesize==0)?intval($allCount/$pagesize):intval($allCount/$pagesize)+1;
             if($page>$totalPage){
                 return [
                     'pagesize'=>$pagesize,
                     'total'=>$allCount,
                     'totalpage'=>$totalPage,
                     'current'=>$page,
                     'list'=>[]
                 ];
             }else{
                 return [
                     'pagesize'=>$pagesize,
                     'total'=>$allCount,
                     'totalpage'=>$totalPage,
                     'current'=>$page,
                     'list'=>array_slice($data,($page-1)*$pagesize,$pagesize)
                 ];
             }
         }
     }

    /**替换 插入数据
     * @param array $fields
     * @param array $values
     * @return bool|Swoole\Database\MySQLiRecord
     */
    public  function  ReplaceInto($fields=[],$values=[]){
        if(is_string($fields)){
            $fieldList="({$fields})";
        }elseif(is_array($fields)){
            $fieldList='('.implode(',',$fields).')';
        }
        if(is_string($values)){
            $valueList="({$values})";
        }elseif(is_array($values)){
            $valueList='('.implode(',',$values).')';
        }
        $sql="replace into {$this->table} {$fieldList} VALUES {$valueList} ";
        echo $sql;
        return $this->db->query($sql);
    }


}