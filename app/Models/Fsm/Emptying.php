<?php
// Last Modified Date: 18-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)  
namespace App\Models\Fsm;

use App\Models\BuildingInfo\Building;
use App\Models\Fsm\EmployeeInfo;
use App\Models\Fsm\ServiceProvider;
use App\Models\Fsm\Application;
use App\Models\User;
use App\Models\Fsm\TreatmentPlant;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Venturecraft\Revisionable\RevisionableTrait;

class Emptying extends Model
{
    use HasFactory;
    use SoftDeletes;
    use RevisionableTrait;

    /**
     * The table name along with the schema.
     *
     * @var String
     */
    protected $table = 'fsm.emptyings';

    /**
     * Enable revisions/history
     *
     * @var bool
     */
    protected $revisionCreationsEnabled = true;

    /**
     * Exclude keeping history of the following columns.
     *
     * @var array
     */
    protected $dontKeepRevisionOf = ['emptied_date', 'service_provider ', 'receipt_image', 'house_image'];

    /**
     * The columns with dates.
     *
     * @var String
     */
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'emptied_date'];

    /**
     * The fillable fields for mass assignment.
     *
     * @var array
     */
    protected $fillable = [
        'application_id',
        'volume_of_sludge',
        'emptied_date',
        'desludging_vehicle_id',
        'treatment_plant_id',
        'house_image',
        'receipt_image',
        'driver',
        'emptier1',
        'start_time',
        'end_time',
        'no_of_trips',
        'receipt_number',
        'total_cost',
        'service_provider_id',
        'user_id',
        'service_receiver_name',
        'service_receiver_gender',
        'service_receiver_contact',
        'emptying_reason',
        'comments',
        'total_time',
        'trip_no',
    ];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */

    /**
     * These attributes are appended to current model through relationships.
     *
     * @var array
     */
    // protected $appends = ['house_number'];

    /**
     * Get the containment associated with the application.
     *
     *
     * @return BelongsTo
     */
    /*public function containments(){
        return $this->belongsTo(Containment::class,'containment_code','id');
    }*/

    /**
     * Get the service provider associated with the application.
     *
     *
     * @return BelongsTo
     */
    public function service_provider()
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    /**
     * Get the building associated with the application.
     *
     *
     * @return BelongsTo
     */
    public function buildings()
    {
        return $this->belongsTo(Building::class, 'house_number', 'house_number');
    }



    /**
     * Get the application associated with the application.
     *
     *
     * @return BelongsTo
     */
    public function application()
    {
        return $this->belongsTo(Application::class, 'application_id', 'id');
    }

    /**
     * Get the user associated with the emptying.
     *
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the vacutug associated with the emptying.
     *
     *
     * @return BelongsTo
     */

    public function vacutug()
    {
        return $this->hasOne('App\Models\Fsm\VacutugType', 'id', 'desludging_vehicle_id');
    }

    /**
     * Get the sludge collection associated with the emptying.
     *
     *
     * @return BelongsTo
     */

    public function sludge_collection()
    {
        return $this->hasOne('App\Models\Fsm\SludgeCollection','application_id','application_id');
    }
    
    public function feedback()
    {
        return $this->hasOne('App\Models\Fsm\Feedback','application_id','application_id');
    }

    /**
     * Get the treatment plant associated with the emptying.
     *
     *
     * @return
     */

    public function treatmentPlant()
    {
        return $this->belongsTo(TreatmentPlant::class, 'treatment_plant_id', 'id');
    }

    /**
     * Accessor function to access and append bin attribute.
     *
     *
     * @return String
     */
    public function getHouseNumberAttribute()
    {
        return $this->application->buildings->bin;
    }

    public function employee_info_driver()
    {
        return $this->belongsTo(EmployeeInfo::class, 'driver', 'id');
    }

    public function employee_info_emptier()
    {
        return $this->belongsTo(EmployeeInfo::class, 'emptier1', 'id');
    }
}
