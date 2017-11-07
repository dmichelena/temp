<?php
namespace Reliese\Database\Eloquent;

use Illuminate\Support\Arr;
use Rees\Sanitizer\Sanitizer;

class BaseValidator
{
    /**
     * @var array
     */
    protected static $rules = [];

	/**
	 * @var array
	 */
	protected static $serverRules = [];

	/**
	 * @var array
	 */
	protected static $filters = [];

    /**
     * @var array
     */
    protected static $messages = [];

	/**
	 * @param $params
	 * @param array $attributes
	 * @param bool $withServerRules
	 * @return \Illuminate\Validation\Validator
	 */
    public static function validate(&$params, $attributes = [], $withServerRules = true)
    {
    	static::filter($params);

        return \Validator::make(
        	$params,
	        static::rules($attributes, $withServerRules),
	        static::$messages
        );
    }


    /**
     * @param $attributes
     * @return array
     */
    protected static function parseAttributes($attributes)
    {
        $keys = static::getAllAttributes();

        if (empty($attributes)) {
            return $keys;
        }
        return Arr::where((array)$attributes, function ($value, $key) use ($keys){
            return in_array($value, $keys);
        });
    }

	/**
	 * Return rules
	 * @param $attributes
	 * @param boolean $withServerRules
	 * @return array
	 */
    public static function rules($attributes, $withServerRules)
    {
        $attributes = static::parseAttributes($attributes);
        $rules = [];
        foreach ($attributes as $attribute) {
            $rule = '';
            if (isset(static::$rules[$attribute])) {
                $rule .= static::$rules[$attribute];
            }
            if (isset(static::$serverRules[$attribute]) && $withServerRules) {
                $serverRules = static::$serverRules[$attribute];
                $rule .= empty($rule) ? '' : '|';
                $rule .= $serverRules;
            }
            if (!empty($rule)) {
                $rules[$attribute] = $rule;
            }
        }

        return $rules;
    }

    /**
     * @return array
     */
    protected static function getAllAttributes()
    {
        $keys = array_keys(
            array_merge(
                static::$rules,
                static::$serverRules
            )
        );

        return $keys;
    }

	/**
	 * @param $params
	 */
	protected static function filter(&$params)
	{
		static::sanitizer()->sanitize(static::$filters, $params);
	}

	/**
	 * @return Sanitizer
	 */
	protected static function sanitizer()
	{
		return new Sanitizer();
	}
}