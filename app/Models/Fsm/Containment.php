<?php

namespace App\Models\Fsm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Venturecraft\Revisionable\RevisionableTrait;
use App\Models\BuildingInfo\Building;
use App\Models\Concerns\HasPublicId;
class Containment extends Model
{
    use SoftDeletes;
    use HasPublicId;
    use RevisionableTrait;

    protected $revisionCreationsEnabled = true;
    protected $table = 'fsm.containments';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $with =['containmentType'];

     /*public function applications()
     {
         return $this->hasMany(Application::class,'containment_code','containment_code');
     }*/

    public function buildings()
    {
        return $this->belongsToMany('App\Models\BuildingInfo\Building', 'building_info.build_contains', 'containment_id', 'bin','id')->whereNull('build_contains.deleted_at')->withPivot(['bin', 'containment_id']);
    }

    public function emptyingService()
    {
        return $this->hasMany('App\Models\Fsm\Application', 'containment_id');
    }
    public function applications()
    {
        return $this->hasMany('App\Models\Fsm\Application', 'containment_id');
    }

    public function containmentType()
    {
        return $this->belongsTo('App\Models\Fsm\ContainmentType', 'type_id', 'id');
    }
}
