<?php
// Last Modified Date: 09-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)  (© ISPL, 2024)
namespace App\Models\UtilityInfo;
use App\Models\Fsm\TreatmentPlant;
use App\Models\BuildingInfo\Building;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Venturecraft\Revisionable\RevisionableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class Drain extends Model
{
    use HasFactory;
    use RevisionableTrait;
    use SoftDeletes;

    protected $revisionCreationsEnabled = true;
    protected $table = 'utility_info.drains';
    protected $primaryKey = 'code';
    protected $keyType = 'string';
    public $incrementing = false;

    public function buildings(){
        return $this->hasMany(Building::class,'drain_code','code');
    }

    public function treatmentPlant(){
        return $this->belongsTo(TreatmentPlant::class,'treatment_plant_id','id');
    }
}
