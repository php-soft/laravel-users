<?php

namespace PhpSoft\Illuminate\Users\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use UserTrait;

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
    protected $fillable = ['name', 'email', 'password'];

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

        return parent::create($attributes)->fresh();
    }

    /**
     * Change password
     * 
     * @param  array  $attributes
     * @return User
     */
    public function changePassword($newPassword)
    {
        $user = $this;

        $user['password'] = bcrypt($newPassword);

        return $user->save();
    }
}
