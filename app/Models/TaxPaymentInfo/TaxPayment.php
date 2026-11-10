<?php

namespace App\Models\TaxPaymentInfo;

use App\Services\PiiEncryptionService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxPayment extends Model
{
    use HasFactory;

    protected $table = 'taxpayment_info.tax_payments';
    protected $fillable = [
        'tax_code', 'owner_name', 'owner_contact', 'last_payment_date'
        
    ];

    public function setOwnerNameAttribute($value): void
    {
        $this->setEncryptedPiiAttribute('owner_name', $value);
    }

    public function setOwnerContactAttribute($value): void
    {
        $this->setEncryptedPiiAttribute('owner_contact', $value);
    }

    private function setEncryptedPiiAttribute(string $attribute, $value): void
    {
        $normalized = $value === null ? null : (string) $value;

        $this->attributes[$attribute] = app(PiiEncryptionService::class)
            ->encrypt($normalized);
    }

    public static function selectAll(){
        // Generic reads must not expose encrypted PII. Use the explicit
        // privileged presenter/export services when owner data is required.
        return TaxPayment::select('tax_code', 'last_payment_date');
    }
}
