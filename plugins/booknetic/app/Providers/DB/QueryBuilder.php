<?php

namespace BookneticApp\Providers\DB;

use BookneticApp\Providers\Helpers\StringUtil;
use BookneticApp\Providers\Translation\Translator;

/**
 * @template TModel of Model
 */
class QueryBuilder extends \stdClass
{
    /** @var class-string<Model>|class-string<Translator>|null */
    private ?string $model;
    private array $_properties_qb = [
        'whereArr'         =>  [],
        'orderByArr'       =>  [],
        'groupByArr'       =>  [],
        'columnsArr'       =>  [],
        'joins'            =>  [],
        'offset'           =>  null,
        'limit'            =>  null,
        'excludeScopes'    =>  [],
        'where_id'         =>  null,
        'withTranslations' => false
    ];

    /**
     * @param class-string<TModel> $model
     */    public function __construct(string $model)
    {
        $this->model = $model;

        $model::boot($this);
    }

    public function __call($name, $arguments)
    {
        $scopeMethodName = 'scope' . StringUtil::snakeCaseToCamel($name);

        if (method_exists($this->model, $scopeMethodName) && is_callable([ $this->model, $scopeMethodName ])) {
            array_unshift($arguments, $this);
            call_user_func_array([ $this->model, $scopeMethodName ], $arguments);
        } elseif (method_exists($this->model, $name) && is_callable([ $this->model, $name ])) {
            return call_user_func_array([ $this->model, $name ], $arguments);
        }

        return $this;
    }

    /**
     * @param $id
     *
     * @return Collection|static
     */
    public function get($id = null)
    {
        return $this->where($this->model::getField('id'), $id)->fetch();
    }

    public function where($field, $valueOrSymbol = false, $value2 = false, $combinator = 'AND')
    {
        if (is_array($field) && $valueOrSymbol === false) {
            foreach ($field as $fieldOfArr => $valueOfArr) {
                $this->where($fieldOfArr, $valueOfArr);
            }

            return $this;
        }

        $symbol = $value2 === false ? '=' : $valueOrSymbol;
        $value  = $value2 === false ? $valueOrSymbol : $value2;

        if ($field === 'id' && is_numeric($value)) {
            $this->_properties_qb['where_id'] = $value;
        }

        $this->_properties_qb['whereArr'][] = [ $field, $symbol, $value, $combinator ];

        return $this;
    }

    public function orWhere($field, $valueOrSymbol = false, $value2 = false)
    {
        return $this->where($field, $valueOrSymbol, $value2, 'OR');
    }

    public function whereId($value)
    {
        return $this->where('id', $value);
    }

    public function whereIsNull($field)
    {
        return $this->where($field, 'is', null);
    }

    public function whereIsNotNull($field)
    {
        return $this->where($field, 'is not', null);
    }

    public function whereFindInSet($field, $value, $combinator = 'AND')
    {
        return $this->where($field, 'find_in_set', $value, $combinator);
    }

    public function orWhereFindInSet($field, $value)
    {
        return $this->whereFindInSet($field, $value, 'OR');
    }

    public function like($field, $value, $combinator = 'AND')
    {
        return $this->where($field, 'like', '%' . $value . '%', $combinator);
    }

    public function orLike($field, $value)
    {
        return $this->like($field, $value, 'OR');
    }

    public function orderBy($arr)
    {
        $this->_properties_qb['orderByArr'] = array_merge($this->_properties_qb['orderByArr'], (array)$arr);

        return $this;
    }

    public function groupBy($arr)
    {
        $this->_properties_qb['groupByArr'] = array_merge($this->_properties_qb['groupByArr'], (array)$arr);

        return $this;
    }

    public function select($arr, $unselect_old_fields = false)
    {
        if ($unselect_old_fields) {
            $this->_properties_qb['columnsArr'] = [];
        }

        $this->_properties_qb['columnsArr'] = array_merge($this->_properties_qb['columnsArr'], (array)$arr);

        return $this;
    }

    public function selectSubQuery(QueryBuilder $subQuery, $alias)
    {
        $this->_properties_qb['columnsArr'][] = '( ' . $subQuery->toSql() . ' ) AS ' . $alias;

        return $this;
    }

    public function limit($limit)
    {
        $this->_properties_qb['limit'] = $limit;

        return $this;
    }

    public function offset($offset)
    {
        $this->_properties_qb['offset'] = $offset;

        return $this;
    }

    private function join($joinTo, $joinType, $select_fields = 'id', $field1 = null, $field2 = null, $unselect_fields = false, $alias = null)
    {
        $joinTo = $this->normalizeTableName($joinTo);

        $relations = $this->model::$relations;

        if (!isset($relations[$joinTo]) && (is_null($field1) || is_null($field2)) && !is_array($field1)) {
            return $this;
        }

        $tableName = isset($relations[$joinTo]) ? $relations[$joinTo][0]::getTableName() : $joinTo;
        $tableAlias = $alias ?: $joinTo;

        if (!is_array($field1)) {
            $field1 = ! is_null($field1) ? $field1 : ($alias ? "`{$alias}`." : DB::table($tableName) . '.') . (isset($relations[$joinTo]) ? $relations[$joinTo][1] : 'id');
            $field2 = ! is_null($field2) ? $field2 : DB::table($this->getTableName()) . '.' . (isset($relations[$joinTo]) ? $relations[$joinTo][2] : 'id');
        }

        $this->_properties_qb['joins'][] = [ $tableName, is_array($field1) ? $field1 : [ [ $field1, '=', $field2 ] ], $joinType ,
        $alias
        ];

        if (empty($select_fields)) {
            return $this;
        }

        $select_fields = is_array($select_fields) ? $select_fields : (array)$select_fields;

        if (empty($this->_properties_qb['columnsArr']) && ! $unselect_fields) {
            $this->_properties_qb['columnsArr'][] = DB::table($this->getTableName()) . '.*';
        }

        foreach ($select_fields as &$select_field) {
            $tableRef = $alias ? "`{$alias}`" : DB::table($tableName);
            $select_field = $tableRef . '.' . $select_field . ' AS `' . $tableAlias . '_' . $select_field . '`';
        }

        $this->_properties_qb['columnsArr'] = array_merge($this->_properties_qb['columnsArr'], $select_fields);

        return $this;
    }

    public function leftJoin($joinTo, $select_fields = 'id', $field1 = null, $field2 = null, $unselect_fields = false, $alias = null)
    {
        return $this->join($joinTo, 'LEFT', $select_fields, $field1, $field2, $unselect_fields, $alias);
    }

    public function rightJoin($joinTo, $select_fields = 'id', $field1 = null, $field2 = null, $unselect_fields = false, $alias = null)
    {
        return $this->join($joinTo, 'RIGHT', $select_fields, $field1, $field2, $unselect_fields, $alias);
    }

    public function innerJoin($joinTo, $select_fields = 'id', $field1 = null, $field2 = null, $unselect_fields = false, $alias = null)
    {
        return $this->join($joinTo, 'INNER', $select_fields, $field1, $field2, $unselect_fields, $alias);
    }

    public function leftJoinSelf(string $alias, array $selectFields, string $field1 = null, string $field2 = null, bool$unselect_fields = false): self
    {
        $tableName = $this->model::getTableName();

        return $this->leftJoin($tableName, $selectFields, $field1, $field2, $unselect_fields, $alias);
    }

    public function withoutGlobalScope($scope)
    {
        if (! in_array($scope, $this->_properties_qb['excludeScopes'])) {
            $this->_properties_qb['excludeScopes'][] = $scope;
        }

        return $this;
    }

    private function bootGlobalScopes($queryType)
    {
        foreach ($this->model::getGlobalScopes() as $scope => $closure) {
            if (! in_array($scope, $this->_properties_qb['excludeScopes'])) {
                call_user_func($closure, $this, $queryType);
            }
        }
    }

    /**
     * @return TModel
     */
    public function fetch()
    {
        $this->bootGlobalScopes('select');

        $this->model::trigger('retrieving', $this);

        $data = DB::fetch($this->getTableName(), $this->getWhereArr(), $this->_properties_qb['orderByArr'], $this->_properties_qb['columnsArr'], $this->_properties_qb['offset'], $this->_properties_qb['limit'], $this->_properties_qb['groupByArr'], $this->_properties_qb['joins']);

        if (!$data) {
            return $data;
        }

        $result = new Collection($data, $this->model);

        if ($this->_properties_qb[ 'withTranslations' ]) {
            $result = $this->model::translateData($result);
        }

        $this->model::trigger('retrieved', $result);

        return $result;
    }

    /**
     * @return array<TModel>
     */
    public function fetchAll()
    {
        $this->bootGlobalScopes('select');

        $this->model::trigger('retrieving', $this);

        $data = DB::fetchAll($this->getTableName(), $this->getWhereArr(), $this->_properties_qb['orderByArr'], $this->_properties_qb['columnsArr'], $this->_properties_qb['offset'], $this->_properties_qb['limit'], $this->_properties_qb['groupByArr'], $this->_properties_qb['joins']);
        $returnData = [];

        foreach ($data as $row) {
            $result = new Collection($row, $this->model);
            $this->model::trigger('retrieved', $result);

            if ($this->_properties_qb[ 'withTranslations' ]) {
                $result = $this->model::translateData($result);
            }

            $returnData[] = $result;
        }

        return $returnData;
    }

    public function fetchAllAsArray(): array
    {
        $results = $this->fetchAll();

        return array_map(static fn (Collection $result) => $result->toArray(), $results);
    }

    public function toSql()
    {
        $this->bootGlobalScopes('select');

        return DB::selectQuery($this->getTableName(), $this->getWhereArr(), $this->_properties_qb['orderByArr'], $this->_properties_qb['columnsArr'], $this->_properties_qb['offset'], $this->_properties_qb['limit'], $this->_properties_qb['groupByArr'], $this->_properties_qb['joins']);
    }

    public function count()
    {
        $prevCols = $this->_properties_qb['columnsArr'];

        $count = $this->select(['count(0) as `row_count`'], true)->fetch();
        $count = $count->row_count ?? 0;

        $this->_properties_qb['columnsArr'] = $prevCols;

        return $count;
    }

    public function countGroupBy()
    {
        $prevCols = $this->_properties_qb['columnsArr'];

        $result = $this->select('0', true)->fetchAll();

        $this->_properties_qb['columnsArr'] = $prevCols;

        return count($result);
    }

    public function sum($column)
    {
        $prevCols = $this->_properties_qb['columnsArr'];

        $sum = $this->select(['SUM('.$column.') as `sum_column`'], true)->fetch()->sum_column;

        $this->_properties_qb['columnsArr'] = $prevCols;

        return $sum;
    }

    public function update($data = [])
    {
        $this->arrayToPropertis($data);
        $this->bootGlobalScopes('update');

        if ($this->model::trigger('updating', $this) === false) {
            return false;
        }

        $result = DB::update($this->getTableName(), $this->getProperties(), $this->getWhereArr());

        $this->model::trigger('updated', $this);

        return $result;
    }

    public function delete()
    {
        $this->bootGlobalScopes('delete');

        $deletedId = $this->_properties_qb['where_id'];

        if (! empty($deletedId) && $this->model::trigger('deleting', $deletedId) === false) {
            return false;
        }

        $result = DB::delete($this->getTableName(), $this->_properties_qb['whereArr']);

        if (! empty($deletedId)) {
            $this->model::trigger('deleted', $deletedId);
        }

        return $result;
    }

    public function insert($data = [])
    {
        $this->arrayToPropertis($data);
        $this->bootGlobalScopes('insert');

        if ($this->model::trigger('creating', $this) === false) {
            return false;
        }

        $result = DB::DB()->insert(DB::table($this->getTableName()), $this->getProperties());

        $this->model::trigger('created', $this);

        return $result;
    }

    public function getTableName()
    {
        return $this->model::getTableName();
    }

    private function normalizeTableName($tableName)
    {
        if (is_subclass_of($tableName, Model::class)) {
            $tableName = $tableName::getTableName();
        }

        return $tableName;
    }

    public function getWhereArr()
    {
        return $this->_properties_qb['whereArr'];
    }

    public function setWhereArr($whereArr)
    {
        $this->_properties_qb['whereArr'] = $whereArr;

        return $this;
    }

    public function getModel()
    {
        return $this->model;
    }

    private function arrayToPropertis($array)
    {
        foreach ($array as $key => $value) {
            $this->$key = $value;
        }
    }

    public function getProperties()
    {
        $properties = get_object_vars($this);

        unset($properties['_properties_qb']);
        unset($properties['model']);

        return $properties;
    }

    public function isGroupQuery(): bool
    {
        return ! empty($this->_properties_qb[ 'groupByArr' ]);
    }

    public function withTranslations(): QueryBuilder
    {
        $this->_properties_qb[ 'withTranslations' ] = true;

        return $this;
    }
}
