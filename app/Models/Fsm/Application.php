<?php
// Last Modified Date: 18-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)
namespace App\Models\Fsm;

use App\Models\BuildingInfo\Building;
use App\Models\UtilityInfo\Roadline;
use App\Models\Fsm\TreatmentPlant;
use App\Models\Fsm\SludgeCollection;
use App\Models\Fsm\SludgeCollectionLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Venturecraft\Revisionable\RevisionableTrait;

class Application extends Model
{
    use HasFactory;
    use SoftDeletes;
    use RevisionableTrait;

    /**
     * The table name along with the schema.
     *
     * @var String
     */
    protected $table= 'fsm.applications';

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'sludge_collection_status' => 'integer',
        'emptying_status' => 'integer',
        'is_anf' => 'boolean',
    ];

    /**
     * Enable revisions/history
     *
     * @var bool
     */
    protected $revisionCreationsEnabled = true;

    /**
     * Exclude keeping history of the following columns.
     *
     * @var bool
     */
    protected $dontKeepRevisionOf = ['containment_id','application_date','user_id'];

    /**
     * The table name along with the schema.
     *
     * @var String
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'application_date',
        'proposed_emptying_date',
    ];

    /**
     * The fillable fields for mass assignment.
     *
     * @var array
     */
    protected $fillable = [
        'road_code',
        'bin',
        'ward',
        'applicant_name',
        'applicant_gender',
        'applicant_contact',
        'customer_name',
        'customer_gender',
        'customer_contact',
        'proposed_emptying_date',
        'service_provider_id',
        'emergency_desludging_status',
        'containment_id',
        'trip_count',
        'is_anf',
        'anf_locality',
        'anf_nearest_locality'
    ];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */

    protected $with = ['service_provider','feedback'];


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
    public function service_provider(){
        return $this->belongsTo(ServiceProvider::class, 'service_provider_id','id');
    }

    /**
     * Get the building associated with the application.
     *
     *
     * @return BelongsTo
     */
    public function buildings(){
        return $this->belongsTo(Building::class,'bin','bin');
    }

    /**
     * Get the emptyings associated with the application.
     *
     *
     * @return HasOne
     */
    public function emptying(){
        return $this->hasOne(Emptying::class,'application_id','id');
    }

    /**
     * Get the help desk associated with the application.
     *
     *
     * @return BelongsTo
     */
    /*public function help_desk(){
        return $this->belongsTo(HelpDesk::class,'user_id','id');
    }*/


    public function feedback()
    {
        return $this->hasOne(Feedback::class, 'application_id', 'id');
    }

    public function sludge_collection()
    {
        return $this->hasOne(SludgeCollection::class, 'application_id', 'id');
    }

    public function sludge_collection_log()
    {
        return $this->hasMany(SludgeCollectionLog::class, 'application_id', 'id');
    }

}

