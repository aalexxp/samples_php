<?php

namespace App\Commands;


use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Input;
use Route;

trait SortableTrait
{
    protected static $_searchFieldName = 'field';
    protected static $_orderTypeName = 'sort';

    protected static $_defaultSearchField;
    protected static $_defaultSearchOrder;

    protected $_joins = [];

    protected static $_removeParameters = [];
    protected static $_addParameters = [];

    protected $_simpleOrderByColumns = ['value', 'beds', 'created_at', 'deleted_at', 'updated_at'];
    protected $_simpleOrderByCustomColumns = ['last_activity'];

    /**
     * @param $query \Illuminate\Database\Query\Builder
     *
     * @return mixed
     */
    public function scopeSortable($query)
    {
        $field = Input::has(self::$_searchFieldName) ? Input::get(self::$_searchFieldName) : self::$_defaultSearchField;
        $order = Input::has(self::$_orderTypeName) ? Input::get(self::$_orderTypeName) : self::$_defaultSearchOrder;

        $subFields = explode('.', $field);


        if (\count($subFields) === 1) {
            if ($field && $order) {
                $columns = \Schema::getColumnListing($this->getTable());
                if (\in_array($field, $columns)) {
                    return $this->addCorrectOrderBy($query, $this->getTable().'.'.$field, $order);
                }elseif(in_array($field, $this->_simpleOrderByCustomColumns)){
                    return $this->addCorrectOrderBy($query, $field, $order);
                }
            }
        } else {
            // We need join tables
            $relatedEntity = $this;
            for ($i = 0; $i < (\count($subFields) - 1); $i++) {

                if (method_exists($relatedEntity, $subFields[$i])) {
                    $method = $subFields[$i];

                    /** @var BelongsTo $belongs */
                    $belongs = $relatedEntity->$method();

                    if (!is_a($belongs, BelongsTo::class)) {
                        // We can join only "Belongs To" entities
                        return $query;
                    }

                    $relatedEntity = $belongs->getRelated();

                    $this->_joins[] = [
                        'table' => $relatedEntity->getTable(),
                        'alias' => 'table_with_alias_' . $i . '_' . random_int(1, 999999),
                        'foreign_key' => $belongs->getForeignKey(),
                        'other_key' => $belongs->getOwnerKey()
                    ];
                } else {
                    // This method doesn't exist
                    return $query;
                }
            }

            $field = end($subFields);
            $columns = \Schema::getColumnListing($relatedEntity->getTable());

            if (\in_array($field, $columns)) {
                // Request is correct! We can construct it

                $previousJoin = $this->getTable();

                foreach ($this->_joins AS $join) {
                    $query->leftJoin($join['table'] . ' AS ' . $join['alias'],
                        $previousJoin . '.' . $join['foreign_key'], '=', $join['alias'] . '.' . $join['other_key']);
                    $previousJoin = $join['alias'];
                }

                $query->select([$this->getTable() . '.*']);

                return $this->addCorrectOrderBy($query, $previousJoin . '.' . $field, $order);
            }
        }

        return $query;
    }

    protected function addCorrectOrderBy($query, $field, $order)
    {
        if (\in_array($field, $this->_simpleOrderByColumns)) {
            return $query->orderBy($field, $order);
        }

        return $query->orderByRaw("LOWER({$field}) {$order}");
    }

    public static function link_to_sorting_action($col, $title = null, array $additionalGetParameters = [])
    {
        if (null === $title) {
            $title = str_replace('_', ' ', $col);
            $title = ucfirst($title);
        }

        $searchField = Input::get(self::$_searchFieldName) ?? self::$_defaultSearchField;
        $searchOrder = mb_strtolower(Input::get(self::$_orderTypeName) ?? self::$_defaultSearchOrder);
        $indicator = ($searchField == $col ? ($searchOrder === 'asc' ? '&uarr;' : '&darr;') : null);

        $parameters = array_merge(Route::getCurrentRoute()->parameters(), Input::get(), array(
            self::$_searchFieldName => $col,
            self::$_orderTypeName =>
                mb_strtolower(Input::get(self::$_orderTypeName)) === 'asc' ? 'desc' : 'asc'
        ), $additionalGetParameters, self::$_addParameters);

        if (self::$_removeParameters) {
            foreach (self::$_removeParameters AS $paramName) {
                unset($parameters[$paramName]);
            }
        }

        return link_to_route(Route::currentRouteName(), "$title $indicator", $parameters);
    }

    public static function setDefaultSorting($fieldName, $orderType = 'asc')
    {
        self::$_defaultSearchField = $fieldName;
        self::$_defaultSearchOrder = $orderType;
        if (__CLASS__ !== SortableTrait::class) {
            SortableTrait::setDefaultSorting($fieldName, $orderType);
        }
    }

    public static function addLinkParameters(array $parameters)
    {
        self::$_addParameters = array_merge_recursive(self::$_addParameters, $parameters);
        if (__CLASS__ !== SortableTrait::class) {
            SortableTrait::addLinkParameters($parameters);
        }
    }

    public static function removeLinkParameters(array $parametersList)
    {
        self::$_removeParameters = array_merge(self::$_removeParameters, $parametersList);
        if (__CLASS__ !== SortableTrait::class) {
            SortableTrait::removeLinkParameters($parametersList);
        }
    }
}