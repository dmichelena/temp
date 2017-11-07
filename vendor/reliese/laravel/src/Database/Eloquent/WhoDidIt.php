<?php

/**
 * Created by Cristian.
 * Date: 12/10/16 12:09 AM.
 */
namespace Reliese\Database\Eloquent;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Http\Request;

class WhoDidIt
{
    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Blamable constructor.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    public function creating(Eloquent $model)
    {
        $model->{$this->getCreatedByColumn($model)} = $this->doer();
        $model->{$this->getUpdatedByColumn($model)} = $this->doer();
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    public function updating(Eloquent $model)
    {
        $model->{$this->getUpdatedByColumn($model)} = $this->doer();
    }

    /**
     * @return mixed|string
     */
    protected function doer()
    {
        if (app()->runningInConsole()) {
            return 'CLI';
        }
        return $this->authenticated() ? $this->userId() : '????';
    }

    /**
     * @param Eloquent $model
     * @return string
     */
    protected function getCreatedByColumn(Eloquent $model)
    {
        return $this->checkConstant($model, 'CREATED_BY', 'created_by');
    }

    /**
     * @param Eloquent $model
     * @return string
     */
    protected function getUpdatedByColumn(Eloquent $model)
    {
        return $this->checkConstant($model, 'UPDATED_BY', 'updated_by');
    }

    /**
     * @param Eloquent $model
     * @param $const
     * @param $default
     * @return string
     */
    protected function checkConstant(Eloquent $model, $const, $default)
    {
        $class = get_class($model);
        $const = "{$class}::{$const}";

        return defined($const) ? constant($const) : $default;
    }

    /**
     * @return mixed
     */
    protected function authenticated()
    {
        return $this->request->user();
    }

    /**
     * @return mixed
     */
    protected function userId()
    {
        return $this->authenticated()->user();
    }
}
