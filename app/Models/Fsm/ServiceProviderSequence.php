<?php

namespace App\Models\Fsm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Fsm\ServiceProvider;

class ServiceProviderSequence extends Model
{
    use HasFactory;

    protected $table = 'fsm.service_provider_sequence';

    protected $fillable = ['id','service_provider_id','current_sequence','sequence_order', 'desludging_vehicle_size'];
    public $timestamps = false;

     public function service_provider(){
        return $this->belongsTo(ServiceProvider::class, 'service_provider_id', 'id')
                ->where('status', 'true');
    }

}
