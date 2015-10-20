<?php

namespace PhpSoft\Users\Models;

use Illuminate\Database\Eloquent\Model;


class RoleUser extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'role_user';
    protected $fillable = ['role_id', 'user_id'];
}
