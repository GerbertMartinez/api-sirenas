<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{

    protected $table = "activities";
    protected $primaryKey = "id_activity";

    use SoftDeletes;

}