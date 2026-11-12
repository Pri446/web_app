<?php
// Last Modified Date: 18-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)  
namespace App\Models\Fsm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Venturecraft\Revisionable\RevisionableTrait;
class VacutugType extends Model
{
    use HasFactory;
    use RevisionableTrait;
    use SoftDeletes;
    protected $revisionCreationsEnabled = true;
    protected $table = 'fsm.desludging_vehicles';
  
    public function sludgeCollections()
    {
        return $this->hasMany('App\Models\Fsm\SludgeCollection', 'desludging_vehicle_id', 'id');
    }
    public function emptyings()
    {
        return $this->hasMany(Emptying::class, 'desludging_vehicle_id', 'id');
    }
    public function serviceProvider()
    {
        return $this->belongsTo('App\Models\Fsm\ServiceProvider', 'service_provider_id', 'id');
    }
    public function vacutugType()
    {
        return $this->belongsTo('App\Models\Fsm\ServiceProvider', 'service_provider_id', 'id');
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

