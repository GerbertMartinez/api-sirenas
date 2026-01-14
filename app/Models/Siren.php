<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Siren extends Model
{

    protected $table = "sirens";
    protected $primaryKey = "id_siren";

    public function records()
    {
        return $this->hasMany(Record::class, 'id_siren', 'id_siren');
    }

    public function activities()
    {
        return $this->hasMany(Activity::class, 'id_siren', 'id_siren');
    }

    use SoftDeletes;

}