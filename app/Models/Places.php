<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Places extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'layer_info.places';

    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'unique_reference_id',
        'ward',
        'geom',
        'type',
    ];
}
