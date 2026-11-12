<?php

namespace App\Models\UtilityInfo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Venturecraft\Revisionable\RevisionableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BuildingInfo\Building;
class WaterSupplys extends Model
{
    use HasFactory;
    use RevisionableTrait;
    use SoftDeletes;

    protected $revisionCreationsEnabled = true;
    protected $table = 'utility_info.water_supplys';
    protected $primaryKey = 'code';
    protected $keyType = 'string';
    public $incrementing = false;

        /**
     * Get the buildings associated with the sewer.
     *
     *
     * @return HasMany
     */
    public function buildings(){
        return $this->hasMany(Building::class,'watersupply_pipe_code','code');
    }
}
