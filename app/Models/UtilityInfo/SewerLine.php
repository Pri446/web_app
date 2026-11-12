<?php

namespace App\Models\UtilityInfo;

use App\Models\BuildingInfo\Building;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Venturecraft\Revisionable\RevisionableTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Fsm\TreatmentPlant;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\UtilityInfo\SewerConnection;
class SewerLine extends Model
{
    use HasFactory;
    use RevisionableTrait;
    use SoftDeletes;

    protected $revisionCreationsEnabled = true;
    protected $table = 'utility_info.sewers';
    protected $primaryKey = 'code';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $with =['TreatmentPlant'];
       /**
     * Get the buildings associated with the sewer.
     *
     *
     * @return HasMany
     */
    public function buildings(){
        return $this->hasMany(Building::class,'sewer_code','code');
    }


    public function SewerConnection(){
        return $this->hasMany(SewerConnection::class,'sewer_code','code');
    }

    public function TreatmentPlant(){
        return $this->belongsTo(TreatmentPlant::class,'treatment_plant_id','id');
    }


}

