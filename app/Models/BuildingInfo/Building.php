<?php

namespace App\Models\BuildingInfo;

use App\Models\Fsm\Application;
use App\Models\UtilityInfo\Roadline;
use Illuminate\Database\Eloquent\Model;
use App\Models\BuildingInfo\UseCategory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Venturecraft\Revisionable\RevisionableTrait;
use App\Models\BuildingInfo\SanitationSystem;

use App\Models\LayerInfo\Lic;
use App\Models\Concerns\HasPublicId;
class Building extends Model
{
    use SoftDeletes;
    use HasPublicId;

    use RevisionableTrait;

    protected $revisionCreationsEnabled = true;
    protected $table = 'building_info.buildings';
    protected $primaryKey = 'bin';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ["ward","road_code", "sewer_code","drain_code"];
    protected $with =['StructureType', 'FunctionalUse','SanitationSystem','Owners','Lic'];
    // public function sanitation_system_types(){
    //     return $this->belongsTo(SanitationSystem::class, 'sanitation_system_type_id','id');
    // }

    public function containments()
    {
        return $this->belongsToMany('App\Models\Fsm\Containment', 'App\Models\BuildingInfo\BuildContain', 'bin', 'containment_id')
                ->whereNull('build_contains.deleted_at')
                ->withPivot(['bin', 'containment_id']);
    }

    public function sharedToilets()
    {
        return $this->belongsToMany('App\Models\Fsm\Ctpt', 'fsm.build_toilets', 'bin', 'toilet_id')
        ->whereNull('build_toilets.deleted_at')->withPivot(['bin', 'toilet_id']);
    }

    public function Owners()
    {
        return $this->belongsTo('App\Models\BuildingInfo\Owner', 'bin', 'bin');
    }

    public function StructureType()
    {
        return $this->belongsTo('App\Models\BuildingInfo\StructureType','structure_type_id','id');
    }

    /**
     * Get the applications associated with the building.
     *
     *
     * @return HasMany
     */
    public function applications(){
        return $this->hasMany(Application::class,'bin','bin');
    }

    /**
     * Get the roadline associated with the building.
     *
     *
     * @return BelongsTo
     */
    public function roadlines(){
        return $this->belongsTo(Roadline::class,'road_code','code');
    }

    /**
     * Get the owner associated with the building.
     *
     *
     * @return BelongsTo
     */

    public function functionalUse(){
        return $this->belongsTo(FunctionalUse::class,'functional_use_id','id');
    }
    public function useCategory(){
        return $this->belongsTo(UseCategory::class,'use_category_id','id');
    }



    public function SanitationSystem()
    {

        return $this->belongsTo(SanitationSystem::class,'sanitation_system_id','id');
    }


    public function ctPt()
    {
        return $this->belongsToMany('App\Models\Fsm\Ctpt', 'fsm.build_toilets', 'bin', 'toilet_id')->withPivot(['bin', 'toilet_id'])->whereNull('build_toilets.deleted_at');
    }

    public function WaterSource()
    {

        return $this->belongsTo('App\Models\BuildingInfo\WaterSource','water_source_id','id');
    }

    public function Lic(){
        return $this->belongsTo(Lic::class,'lic_id','id');
    }



}
