<?php
namespace Reliese\Coders\Model;

use Illuminate\Support\Fluent;

class RulesGenerator
{
    const CUSTOM_RULE_TYPE_EMAIL = 'email';
    const CUSTOM_RULE_TYPE_ARRAY = 'array';
    const CUSTOM_RULE_TYPE_URL = 'url';
    const CUSTOM_RULE_TYPE_NOT_REQUIRED = 'not_required';

    const CUSTOM_FILTER_TYPE_STRING_TO_UPPER = 'strtoupper';
    const CUSTOM_FILTER_TYPE_STRING_TO_LOWER = 'strtolower';

    /**
     * @var Model
     */
    protected $model;

    /**
     * @var array
     */
    protected $serverRules = [];

	/**
	 * @var array
	 */
	protected $rules = [];

	/**
	 * @var array
	 */
	protected $filters = [];


    /**
     * @var array
     */
    protected $patterns = [];

    /**
     * @var array
     */
    protected $filterPatterns = [];

    /**
     * @var array
     */
    protected $lookUp = [];

	/**
	 * @param Model $model
	 * @param array $patterns
	 * @param array $filterPatterns
	 * @param array $lookUp
	 */
    public function generate(Model $model, $patterns = [], $filterPatterns = [], $lookUp = [])
    {
        $this->model = $model;
        $this->patterns = $patterns;
        $this->filterPatterns = $filterPatterns;
        $this->lookUp = $lookUp;
        $this->serverRules = [];
        $this->rules = [];
        $this->filters = [];
        $this->generateAllRules();
        $this->generateCustomFilters();
    }

    /**
     * @return array
     */
    public function rules()
    {
        return $this->formatRules();
    }

	/**
	 * @return array
	 */
	public function serverRules()
	{
		return $this->formatServerRules();
	}

	/**
	 * @return array
	 */
	public function filters()
	{
		return $this->formatFilters();
	}

    /**
     *
     */
    protected function generateAllRules()
    {
        $connectionName = $this->model->getConnectionName();
        $tableName = $this->model->getBlueprint()->table();

        foreach ($this->model->getBlueprint()->columns() as $column) {
            $this->generateRules($column);
        }

        $this->generateUniqueRule(
            "{$connectionName}.{$tableName}",
            $this->model->getBlueprint()->primaryKey()
        );

        foreach ($this->model->getBlueprint()->uniques() as $unique) {
          $this->generateUniqueRule("{$connectionName}.{$tableName}", $unique);
        }

        $this->generateFKRule(
            $this->model->getBlueprint()->relations(),
            $this->model->getRelations()
        );
    }

    /**
     * @param Fluent $column
     */
    protected function generateRules(Fluent $column)
    {
        $columnName = $column->get('name');
        if ($this->isRequired($column)) {
            $this->addRequireRule($columnName);
        }

        switch (strtolower($column->get('type'))) {
            case 'string' : $this->addStringRule($columnName, $column);break;
            case 'bool' : $this->addBooleanRule($columnName);break;
            case 'date' : $this->addDateRule($columnName);break;
            case 'int' : $this->addIntRule($columnName);break;
            case 'float' : $this->addFloatRule($columnName, $column);break;
        }

        if ($this->requireCustomRule(static::CUSTOM_RULE_TYPE_ARRAY, $columnName)) {
            $this->addArrayRule($columnName);
        }

        if ($this->requireCustomRule(static::CUSTOM_RULE_TYPE_EMAIL, $columnName)) {
            $this->addEmailRule($columnName);
        }

        if ($this->requireCustomRule(static::CUSTOM_RULE_TYPE_URL, $columnName)) {
            $this->addUrlRule($columnName);
        }

        $this->generateEnumRule($columnName, $column);
    }

    /**
     * @param Fluent $column
     * @return bool
     */
    protected function isRequired(Fluent $column)
    {
        $null = $column->get('nullable');
        $default = $column->get('default');
        $autoincrement = $column->get('autoincrement');
        $hasNotRequiredRule = $this->requireCustomRule(
            static::CUSTOM_RULE_TYPE_NOT_REQUIRED,
            $column->get('name')
        );

        return !$null && !$default && !$autoincrement && !$hasNotRequiredRule;
    }

    /**
     * @param array $rules
     * @return array
     */
    protected function formatRules($rules = [])
    {
        $rules = empty($rules) ? $this->rules : $rules;
        return array_map(function($val){
            return implode('|', $val);
        }, $rules);
    }

    /**
     * @return array
     */
    protected function formatServerRules()
    {
        if (empty($this->serverRules)) {
            return [];
        }
        return $this->formatRules($this->serverRules);
    }

    /**
     * @param $tableName
     * @param $columns
     * @param $references
     */
    protected function generateFkRuleByTable($tableName, $columns, $references)
    {
        $fieldName = array_shift($columns);
        $reference = array_shift($references);

        if (count($columns) == 0) {
            $rule = "exists:{$tableName}";
            $rule = ($fieldName == $reference) ? $rule : $rule . ",{$reference}";
            $this->addServerRule($fieldName, $rule);
            return;
        }

        $extra = ($fieldName == $reference) ? [] : ["{$fieldName}={$reference}"];
        for ($i = 0; $i < count($columns); $i++) {
            $extraField = $columns[$i];
            $fieldReference = $references[$i];
            $extra[] = ($extraField == $fieldReference) ? $extraField : "{$extraField}={$fieldReference}";
        }
        $extra = implode(',', $extra);
        $rule = "exists_with:{$tableName},{$extra}";
        $this->addServerRule($fieldName, $rule);
    }

    /**
     * @param $rule
     * @param $attribute
     * @return bool
     */
    protected function requireCustomRule($rule, $attribute)
    {
        if (!isset($this->patterns[$rule])) {
            return false;
        }
        $patterns = $this->patterns[$rule];
        foreach ($patterns as $pattern) {
            if (preg_match("/$pattern/", $attribute)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $columnName
     * @param Fluent $column
     */
    protected function generateEnumRule($columnName, Fluent $column)
    {
        $values = [];
        foreach ($column->get('enum', []) as $value) {
            $values[] = $value;
        }
        if (empty($values)) {
            return;
        }
        $values = implode(',', $values);
        $this->addRule($columnName, "in:{$values}");
    }

    /**
     * @param $tableName
     * @param Fluent $pk
     */
    protected function generateUniqueRule($tableName, Fluent $pk)
    {
        $columns = $pk->get('columns', []);
        if (count($columns) == 0) {
            return;
        }
        $columnName = array_shift($columns);
        if (count($columns) == 0) {
            $rule = "unique:{$tableName}";
        } else {
            $fields = implode(',', $columns);
            $rule = "unique_with:{$tableName},{$fields}";
        }

        $this->addServerRule($columnName, $rule);
    }

    /**
     * @param Fluent[] $columns
     * @param Relation[] $relations
     */
    protected function generateFKRule($columns, $relations)
    {
        if (empty($relations)) {
            return;
        }
        
        /* @var $column Fluent */
        foreach ($columns as $column) {
            $tableName = $column->get('on', []);
            if (!isset($tableName['table'])) {
                continue;
            }
            $tableName = $tableName['table'];

            $connectionName = $this->searchConnectionName($relations, $tableName);

            if (empty($connectionName)) {
                continue;
            }
            
            $columnsTable = $column->get('columns', []);
            $references = $column->get('references', []);
            if(count($columnsTable) != count($references)) {
                continue;
            }

            $this->generateFkRuleByTable("{$connectionName}.{$tableName}", $columnsTable, $references);
        }
    }

    /**
     * @param $column
     * @param $rule
     */
    protected function addRule($column, $rule)
    {
        if (!isset($this->rules[$column])) {
            $this->rules[$column] = [];
        }
        $this->rules[$column][] = $rule;
    }

    /**
     * @param $column
     * @param $rule
     */
    protected function addServerRule($column, $rule)
    {
        if (!isset($this->serverRules[$column])) {
            $this->serverRules[$column] = [];
        }
        $this->serverRules[$column][] = $rule;
    }

    /**
     * @param $columnName
     * @param Fluent $column
     */
    protected function addStringRule($columnName, Fluent $column)
    {
        $this->addRule($columnName, 'string');
        $this->addFilter($columnName, 'trim');
        $max = $column->get('size');
        if (is_numeric($max)) {
            $this->addRule($columnName, "max:{$max}");
        }
    }

    /**
     * @param $columnName
     */
    protected function addBooleanRule($columnName)
    {
        $this->addRule($columnName, 'boolean');
    }

    /**
     * @param $columnName
     */
    protected function addRequireRule($columnName)
    {
        $this->addRule($columnName, 'required');
    }

    /**
     * @param $columnName
     */
    protected function addDateRule($columnName)
    {
        $this->addRule($columnName, 'date');
    }

    /**
     * @param $columnName
     */
    protected function addIntRule($columnName)
    {
        $this->addRule($columnName, 'integer');
    }

    /**
     * @param $columnName
     * @param Fluent $column
     */
    protected function addFloatRule($columnName, $column)
    {
        $int = $column->get('size');
        $decimals = $column->get('scale');
        $int = $int - $decimals;
        $this->addRule($columnName, "regex:/^\d{0,{$int}}\.\d{0,{$decimals}}$/");
    }

    /**
     * @param $columnName
     */
    protected function addEmailRule($columnName)
    {
        $this->addRule($columnName, static::CUSTOM_RULE_TYPE_EMAIL);
    }

    /**
     * @param $columnName
     */
    protected function addUrlRule($columnName)
    {
        $this->addRule($columnName, static::CUSTOM_RULE_TYPE_URL);
    }

    /**
     * @param $columnName
     */
    protected function addArrayRule($columnName)
    {
        $this->addRule($columnName, static::CUSTOM_RULE_TYPE_ARRAY);
    }

    /**
     * @param Relation[] $relations
     * @param $tableName
     * @return string
     */
    protected function searchConnectionName($relations, $tableName)
    {
        if (isset($this->lookUp[$tableName])) {
            return $this->lookUp[$tableName];
        }
        foreach ($relations as $relation) {
            if ($relation->tableName() == $tableName) {
                return $relation->connectionName();
            }
        }
        return '';
    }

	/**
	 *
	 */
	protected function generateCustomFilters()
	{
		foreach ($this->model->getBlueprint()->columns() as $column) {
			$columnName = $column->get('name');
			if ($this->requireCustomFilter(static::CUSTOM_FILTER_TYPE_STRING_TO_LOWER, $columnName)) {
				$this->addFilter($columnName, static::CUSTOM_FILTER_TYPE_STRING_TO_LOWER);
			}
			if ($this->requireCustomFilter(static::CUSTOM_FILTER_TYPE_STRING_TO_UPPER, $columnName)) {
				$this->addFilter($columnName, static::CUSTOM_FILTER_TYPE_STRING_TO_UPPER);
			}
		}
	}

	/**
	 * @param $filter
	 * @param $attribute
	 * @return bool
	 */
	protected function requireCustomFilter($filter, $attribute)
	{
		if (!isset($this->patterns[$filter])) {
			return false;
		}
		$patterns = $this->patterns[$filter];
		foreach ($patterns as $pattern) {
			if (preg_match("/$pattern/", $attribute)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param $column
	 * @param $filter
	 */
	protected function addFilter($column, $filter)
	{
		if (!isset($this->filters[$column])) {
			$this->filters[$column] = [];
		}
		$this->filters[$column][] = $filter;
	}

	/**
	 * @return array
	 */
	protected function formatFilters()
	{
		if (empty($this->filters)) {
			return [];
		}
		return array_map( function($val) {
			return implode('|', $val);
		}, $this->filters);
	}
}
