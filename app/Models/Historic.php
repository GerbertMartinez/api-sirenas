<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Historic extends Model
{

    protected $table = "historic";
    protected $primaryKey = "id_history";

    function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    function siren()
    {
        return $this->belongsTo(Siren::class, 'id_siren', 'id_siren');
    }

    function web()
    {
        return $this->belongsTo(Web::class, 'id_web', 'id_web');
    }

    use SoftDeletes;

}