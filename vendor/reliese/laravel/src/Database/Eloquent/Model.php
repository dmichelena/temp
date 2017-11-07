<?php

/**
 * Created by Cristian.
 * Date: 01/10/16 03:02 PM.
 */
namespace Reliese\Database\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class Model extends \Illuminate\Database\Eloquent\Model
{
    protected static $validator = BaseValidator::class;
    protected $errors = [];

    /**
     * @param array $attributes
     */
    public function validate($attributes = [])
    {
        $modelAttributes = $this->getAttributes();
        $this->errors = $this->getValidator()
            ->validate($modelAttributes, $attributes)
            ->errors()
            ->toArray();

        $this->setRawAttributes($modelAttributes);
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return empty($this->getErrors());
    }

    /**
     * @param array $attributes
     * @return bool
     */
    public function hasErrors($attributes = [])
    {
        return !empty($this->getErrors($attributes));
    }

    /**
     * @param array $attributes
     * @return array
     */
    public function getErrors($attributes = [])
    {
        if (empty($attributes)) {
            return $this->errors;
        }

        return Arr::only($this->errors, (array)$attributes);
    }

    /**
     * @return BaseValidator
     */
    public function getValidator()
    {
        $classValidator = static::$validator;

        return new $classValidator();
    }

    /**
     * {@inheritdoc}
     */
    protected function castAttribute($key, $value)
    {
        if ($this->hasCustomGetCaster($key)) {
            return $this->{$this->getCustomGetCaster($key)}($value);
        }

        return parent::castAttribute($key, $value);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    protected function hasCustomGetCaster($key)
    {
        return $this->hasCast($key) && method_exists($this, $this->getCustomGetCaster($key));
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function getCustomGetCaster($key)
    {
        return 'from'.ucfirst($this->getCastType($key));
    }

    /**
     * {@inheritdoc}
     */
    public function setAttribute($key, $value)
    {
        if ($this->hasCustomSetCaster($key)) {
            $value = $this->{$this->getCustomSetCaster($key)}($value);
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    private function hasCustomSetCaster($key)
    {
        return $this->hasCast($key) && method_exists($this, $this->getCustomSetCaster($key));
    }

    /**
     * @param string $key
     *
     * @return string
     */
    private function getCustomSetCaster($key)
    {
        return 'to'.ucfirst($this->getCastType($key));
    }

    /**
     * @param $params
     * @return static
     */
    public static function findOrCreate($params)
    {
        $instance = static::find($params);
        if ($instance) {
            return $instance;
        }

        return new static($params);
    }

    /**
     * @param \Closure $callback
     * @param bool $throwException
     * @return mixed
     * @throws \Exception
     */
    public static function transaction(\Closure $callback, $throwException = false)
    {
        try {
            return DB::transaction($callback);
        } catch (\Exception $ex) {
        	dd($ex);
            if ($throwException) {
                throw $ex;
            }
        }

        return false;
    }


    /**
     * Set keys for save query.
     *
     * @param Builder $query
     * @return Builder
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        $keys = $this->getKeyName();
        if ( ! is_array($keys)) {
            return parent::setKeysForSaveQuery($query);
        }

        foreach ($keys as $keyName) {
            $query->where($keyName, '=', $this->getKeyForSaveQuery($keyName));
        }

        return $query;
    }
    /**
     * Get keys for save query.
     *
     * @param 	mixed $keyName
     * @return 	mixed
     */
    protected function getKeyForSaveQuery($keyName = null)
    {
        if (is_null($keyName)) {
            $keyName = $this->getKeyName();
        }

        if (isset($this->original[$keyName])) {
            return $this->original[$keyName];
        }

        return $this->getAttribute($keyName);
    }
}
