<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{

    protected $table = "users";
    protected $primaryKey = "id_user";

    public function webs()
    {
        return $this->belongsToMany(Web::class, 'user_webs','id_user', 'id_web');
    }

    use SoftDeletes;

}