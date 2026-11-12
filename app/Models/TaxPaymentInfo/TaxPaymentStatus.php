<?php

namespace App\Models\TaxPaymentInfo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxPaymentStatus extends Model
{
    use HasFactory;

    protected $table = 'taxpayment_info.tax_payment_status';
    protected $primaryKey = 'tax_code';

    public static function selectAll(){
        return TaxPaymentStatus::select('tax_code', 'last_payment_date');
    }
}
