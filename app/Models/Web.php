<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Web extends Model
{

    protected $table = "webs";
    protected $primaryKey = "id_web";

    public function sirens()
    {
        return $this->belongsToMany(Siren::class, 'web_sirens','id_web', 'id_siren');
    }

    use SoftDeletes;

}