<?php

namespace PhpSoft\Users\Models;

use App\User as AppUser;
use Illuminate\Database\Eloquent\Model;
use PhpSoft\Users\Models\Role;

class User extends Model
{
    use UserTrait;

    const STATUS_ACTIVE_EMAIL = 1;
    const STATUS_BLOCK        = 2;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'location',
        'country',
        'biography',
        'occupation',
        'website',
        'image',
        'birthday',
        'gender'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * Create user
     *
     * @param  array  $attributes
     * @return User
     */
    public static function create(array $attributes = [])
    {
        $attributes['password'] = bcrypt($attributes['password']);

        $user = new AppUser($attributes);
        $user->email    = $attributes['email'];
        $user->password = $attributes['password'];
        $user->save();

        return $user;
    }

    /**
     * Update the model in the database.
     *
     * @param  array  $attributes
     * @return bool|int
     */
    public function update(array $attributes = [])
    {
        if (isset($attributes['password'])) {
            $attributes['password'] = bcrypt($attributes['password']);
        }

        if (!parent::update($attributes)) {
            throw new Exception('Cannot update user.'); // @codeCoverageIgnore
        }
        if (!parent::update($attributes)) {
            throw new Exception('Cannot update user.'); // @codeCoverageIgnore
        }

        return $this->fresh();
    }

    /**
     *
     * @param  array  $options
     * @return array
     */
    public static function browse($options = [], $className = __CLASS__)
    {
        $find = new User();
        $fillable = $find->fillable;

        if (!empty($options['filters'])) {
            $inFilters = array_intersect($fillable, array_keys($options['filters']));

            foreach ($inFilters as $key) {
                $find = ($options['filters'][$key] == null) ? $find : $find->where($key, 'LIKE', $options['filters'][$key]);
            }
        }

        $total = $find->count();

        if (!empty($options['order'])) {
            foreach ($options['order'] as $field => $direction) {
                if (in_array($field, $fillable)) {
                    $find = $find->orderBy($field, $direction);
                }
            }

            $find = $find->orderBy('id', 'DESC');
        }

        if (!empty($options['offset'])) {
            $find = $find->skip($options['offset']);
        }

        if (!empty($options['limit'])) {
            $find = $find->take($options['limit']);
        }

        if (!empty($options['cursor'])) {
            $find = $find->where('id', '<', $options['cursor']);
        }

        return [
            'total'  => $total,
            'offset' => empty($options['offset']) ? 0 : $options['offset'],
            'limit'  => empty($options['limit']) ? 0 : $options['limit'],
            'data'   => $find->get(),
        ];
    }

    /**
     * set status is block
     *
     * @param  int $status
     * @return int
     */
    public function block()
    {
        $this->status = $this->status | User::STATUS_BLOCK;
        return $this->save();
    }

    /**
     * set status is non block
     *
     * @param  int $status
     * @return int
     */
    public function unblock()
    {
        $this->status = $this->status & ~User::STATUS_BLOCK;
        return $this->save();
    }

    /**
     * check status is block
     *
     * @param  int  $status
     * @return boolean
     */
    public function isBlock()
    {
        return (User::STATUS_BLOCK)==($this->status & User::STATUS_BLOCK);
    }
}
