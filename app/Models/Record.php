<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Record extends Model
{

    protected $table = "records";
    protected $primaryKey = "id_record";

    use SoftDeletes;

}