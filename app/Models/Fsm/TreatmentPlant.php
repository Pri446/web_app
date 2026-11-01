<?php
// Last Modified Date: 18-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)  
namespace App\Models\Fsm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Venturecraft\Revisionable\RevisionableTrait;
use App\Models\Fsm\Emptying;
use App\Models\UtilityInfo\Drain;
use App\Models\UtilityInfo\SewerLine;
class TreatmentPlant extends Model
{
    use HasFactory;
    use SoftDeletes;
    use RevisionableTrait;
    protected $revisionCreationsEnabled = true;
    protected $table = 'fsm.treatment_plants';
    protected $primaryKey = 'id';

    public function annualSustainability()
    {
        return $this->hasMany(TreatmentPlantAnnualSustainability::class, 'treatment_plant_id')->orderByDesc('data_year');
    }

     public function users()
     {
         return $this->hasMany('App\Models\User', 'treatment_plant_id', 'id');
     }

     public function sludgeCollections()
     {
         return $this->hasMany('App\Models\Fsm\SludgeCollection');
     }

    public function emptyings()
    {
        return $this->hasMany(Emptying::class);
    }

    public function drain()
    {
        return $this->hasMany(Drain::class);
    }

    public function sewer()
    {
        return $this->hasMany(SewerLine::class);
    }

    public function treatmentplantTests()
{
    return $this->hasMany(TreatmentPlantTest::class, 'treatment_plant_id');
}
         /**
     * Scope a query to only include operational status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOperational($query)
    {
        return $query->where('status', 1);
    }

}
