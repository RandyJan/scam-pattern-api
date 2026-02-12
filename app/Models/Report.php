<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportStatements extends Model
{

    protected $fillable = [
        'statement',
        'locale',
    ];
}
