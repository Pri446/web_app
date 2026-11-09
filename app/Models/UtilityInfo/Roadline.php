<?php

namespace App\Models\UtilityInfo;

use App\Models\BuildingInfo\Building;
use App\Models\Fsm\Application;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Venturecraft\Revisionable\RevisionableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\UtilityInfo\SewerLine;
use App\Models\UtilityInfo\Drains;
use App\Models\UtilityInfo\WaterSupplys;
use App\Jobs\RunTopologyUpdate;

class Roadline extends Model
{
    use HasFactory;
    use RevisionableTrait;
    use SoftDeletes;
    protected $revisionCreationsEnabled = true;
    protected $table = 'utility_info.roads';
    protected $primaryKey = 'code';
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * Get the buildings associated with the road.
     *
     *
     * @return HasMany
     */
    public function buildings(){
        return $this->hasMany(Building::class,'road_code','code');
    }

    
    /**
     * Get the sewer associated with the road.
     *
     *
     * @return HasMany
     */
    public function sewers(){
        return $this->hasMany(SewerLine::class,'road_code','code');
    }
    
    
    /**
     * Get the sewer associated with the road.
     *
     *
     * @return HasMany
     */
    public function drains(){
        return $this->hasMany(Drain::class,'road_code','code');
    }
    
    /**
     * Get the sewer associated with the road.
     *
     *
     * @return HasMany
     */
    public function water_supply(){
        return $this->hasMany(WaterSupplys::class,'road_code','code');
    }

    
 protected static function booted()
    {
        static::saved(function ($road) {
            // Run the function in background
             RunTopologyUpdate::dispatch($road->code);
        });
    }


}
