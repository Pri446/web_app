<?php

namespace App\Models\Fsm;

use Illuminate\Database\Eloquent\Model;

class TreatmentPlantAnnualSustainability extends Model
{
    protected $table = 'cwis.treatment_plant_annual_sustainability';
    protected $guarded = ['id'];

    public const VOLUMES = [
        'treated_effluent_generated_volume' => 'Treated Effluent Generated (KLD)',
        'treated_effluent_reused_volume' => 'Treated Effluent Reused (KLD)',
        'treated_biosolids_generated_volume' => 'Treated Biosolids Generated (KLD)',
        'treated_biosolids_reused_volume' => 'Treated Biosolids Reused (KLD)',
    ];

    protected $casts = [
        'data_year' => 'integer',
        'treated_effluent_generated_volume' => 'decimal:3',
        'treated_effluent_reused_volume' => 'decimal:3',
        'treated_biosolids_generated_volume' => 'decimal:3',
        'treated_biosolids_reused_volume' => 'decimal:3',
    ];
}
