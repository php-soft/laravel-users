<?php

namespace PhpSoft\Users\Models;

use Zizaco\Entrust\EntrustPermission;

class Permission extends EntrustPermission
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'display_name', 'description'];

    /**
     * Create the model in the database.
     *
     * @param  array  $attributes
     * @return Permission
     */
    public static function create(array $attributes = [])
    {
        return parent::create($attributes)->fresh();
    }

    /**
     * Update the model in the database.
     *
     * @param  array  $attributes
     * @return bool|int
     */
    public function update(array $attributes = [])
    {
        if (!parent::update($attributes)) {
            throw new Exception('Cannot update permission.'); // @codeCoverageIgnore
        }

        return $this->fresh();
    }

    /**
     * Browse items
     * 
     * @param  array  $options
     * @return array
     */
    public static function browse($options = [])
    {
        $find = new Permission();
        $fillable = $find->fillable;

        $total = $find->count();

        if (!empty($options['order'])) {
            foreach ($options['order'] as $field => $direction) {
                if (in_array($field, $fillable)) {
                    $find = $find->orderBy($field, $direction);
                }
                $find = $find->orderBy('id', 'DESC');
            }
        }

        if (!empty($options['offset'])) {
            $find = $find->skip($options['offset']);
        }

        if (!empty($options['limit'])) {
            $find = $find->take($options['limit']);
        }

        return [
            'total'  => $total,
            'offset' => empty($options['offset']) ? 0 : $options['offset'],
            'limit'  => empty($options['limit']) ? 0 : $options['limit'],
            'data'   => $find->get(),
        ];
    }
}
