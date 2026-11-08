<?php
// Last Modified Date: 18-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)  
namespace App\Models\Fsm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Venturecraft\Revisionable\RevisionableTrait;

class SludgeCollectionLog extends Model
{
    /**
     * The table name along with the schema.
     *
     * @var String
     */
    protected $table= 'fsm.sludge_collections_log';
    use SoftDeletes;

    use RevisionableTrait;
    protected $revisionCreationsEnabled = true;
    protected $with=['applications','treatmentplants','emptying'];
    public function treatmentplants()
    {
        return $this->belongsTo('App\Models\Fsm\TreatmentPlant','treatment_plant_id','id');
    }

    public function serviceProvider()
    {
        return $this->belongsTo('App\Models\Fsm\ServiceProvider', 'service_provider_id', 'id');
    }

    public function applications()
    {
        return $this->belongsTo('App\Models\Fsm\Application', 'application_id');
    }
    
    public function emptying()
    {
        return $this->belongsTo('App\Models\Fsm\Emptying', 'application_id', 'application_id');
    }
    public function vacutug()
    {
        return $this->belongsTo('App\Models\Fsm\VacutugType', 'desludging_vehicle_id', 'id');
    }
    
}
