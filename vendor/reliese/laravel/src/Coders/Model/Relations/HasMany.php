<?php

/**
 * Created by Cristian.
 * Date: 11/09/16 09:26 PM.
 */
namespace Reliese\Coders\Model\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class HasMany extends HasOneOrMany
{
    /**
     * @return string
     */
    public function hint()
    {
        return '\\'.Collection::class;
    }

    /**
     * @return string
     */
    public function name()
    {
        if ($this->parent->usesSnakeAttributes()) {
            return Str::snake(Str::plural(Str::singular($this->related->getTable())));
        }

        return Str::camel(Str::plural(Str::singular($this->related->getTable())));
    }

    /**
     * @return string
     */
    public function method()
    {
        return 'hasMany';
    }

    /**
     * @return string
     */
    public function connectionName()
    {
        return $this->related->getConnectionName();
    }

    /**
     * @return string
     */
    public function tableName()
    {
        return $this->related->getTable();
    }
}
