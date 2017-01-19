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
    //并发查询
    const COCURRENCY_QUERY = 0;

    //普通查询
    const NORMAL_QUERY = 1;

    public $primary = 'id';

    public $foreignkey = 'fid';

    /**sql builder
     * @var \SqlBuilder
     */
    public $sqlBuilder;

    /** 扩展构造函数
     * @param Swoole $swoole
     * @param string $db_key
     */
    function __construct(\Swoole $swoole, $db_key = '')
    {
        $this->sqlBuilder = new SqlBuilder();
        parent::__construct($swoole, $db_key);
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

    /**
     * @return \SqlBuilder
     */
    public function sqlBuilder()
    {
        return $this->sqlBuilder;
    }

    /**
     * @param $array
     * @param int $queryType
     * @return bool|Swoole\Database\MySQLiRecord
     */
    function Puts($array, $queryType = self::NORMAL_QUERY)
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
        //COMYSQL
        if (self::COCURRENCY_QUERY == $queryType) {
            return $this->swoole->codb->query($sqlInsert);
        } else {
            return $this->db->query($sqlInsert);
        }
    }

    /**通过表达式进行update是针对update的一个扩展 支持不同情况
     * @param $data
     * @param $prms
     * @return Swoole\Database\MySQLiRecord
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
            $condition = implode(' and ', $arrSetExpr);
            $sql = ($condition == '' ? $sql : $sql . '  where ' . $condition);
        }
        return $this->db->query($sql);
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
     * @param int $pageSize
     * @param int $pageNo
     * @param string $count_fields
     * @param bool|true $htmlOn
     * @return array
     * @throws \Exception
     */
    public function getPages($params, $pageSize = 1, $pageNo = 1, $count_fields = "1", $htmlOn = true)
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
        //if (!isset($params['order']))
        //{
        //   $params['order'] = "`{$this->table}`.{$this->primary} desc";
        //}
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

    /**
     * @param $params
     * @param int $pageSize
     * @param int $pageNo
     * @return array
     * @throws \Exception
     */
    public function GetsPageWithoutCount($params, $pageSize = 1, $pageNo = 1)
    {
        if (empty($params)) {
            throw new \Exception("no params.");
        }
        $pager = null;
        $params['page'] = $pageNo;
        $selectdb = new SelectDB($this->db);
        $selectdb->from($this->table);
        $selectdb->count_fields = false;
        $selectdb->primary = $this->primary;
        $selectdb->select($this->select);
        $selectdb->page_size = $pageSize;
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
            'pagesize' => $pager->pagesize,
            'current' => $pager->page
        ];
    }


    /**
     * @param $params
     * @return array
     * @throws \Exception
     */
    public function  GetOne($params)
    {
        $params['limit'] = 1;
        $result = $this->gets($params);
        return empty($result) ? [] : $result[0];
    }

    /**替换 插入数据
     * @param array $fields
     * @param array $values
     * @return bool|Swoole\Database\MySQLiRecord
     */
    public function  ReplaceInto($fields = [], $values = [])
    {
        if (is_string($fields)) {
            $fieldList = "({$fields})";
        } elseif (is_array($fields)) {
            $fieldList = '(' . implode(',', $fields) . ')';
        }
        if (is_string($values)) {
            $valueList = "({$values})";
        } elseif (is_array($values)) {
            $valueList = '(' . implode(',', $values) . ')';
        }
        $sql = "replace into {$this->table} {$fieldList} VALUES {$valueList} ";
        return $this->db->query($sql);
    }

    /**替换 插入数据
     * @param array $fields
     * @param array $values
     * @return bool|Swoole\Database\MySQLiRecord
     */
    public function  ReplaceIntoCombine($fields = [], $values = [])
    {
        if (is_string($fields)) {
            $fieldList = "({$fields})";
        } elseif (is_array($fields)) {
            foreach ($fields as &$field) {
                $field = "`$field`";
            }
            $fieldList = '(' . implode(',', $fields) . ')';
        }
        if (is_string($values)) {
            $valueList = "({$values})";
        } elseif (is_array($values)) {
            foreach ($values as &$value) {
                $value = "'$value'";
            }
            $valueList = '(' . implode(',', $values) . ')';
        }
        if (count($fields) > 1 && count($values) > 1) {
            for ($i = 1; $i < count($fields); $i++) {
                if (!empty($values[$i])) {
                    $updates[] = "{$fields[$i]}={$values[$i]}";
                }
            }
        }
        $sql = "insert into {$this->table} {$fieldList} VALUES {$valueList} ";
        if (!empty($updates)) {
            $sql .= "   ON DUPLICATE KEY UPDATE " . implode(",", $updates);
        }
        return $this->db->query($sql);
    }

    /**
     * @return bool
     */
    public function Start()
    {
        return $this->db->start();
    }

    /**
     * @return bool
     */
    public function  Commit()
    {
        return $this->db->commit();
    }

    /**
     * @return bool
     */
    public function  Rollback()
    {
        return $this->db->rollback();
    }
}