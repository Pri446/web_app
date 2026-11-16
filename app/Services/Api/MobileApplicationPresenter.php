<?php

namespace App\Services\Api;

use App\Models\Fsm\Application;
use App\Services\ApplicationCustomerPiiPresenter;

class MobileApplicationPresenter
{
    private ApplicationCustomerPiiPresenter $customerPiiPresenter;

    public function __construct(
        ApplicationCustomerPiiPresenter $customerPiiPresenter
    ) {
        $this->customerPiiPresenter = $customerPiiPresenter;
    }

    /**
     * Return the readable customer/owner name required by mobile while
     * removing the remaining encrypted customer PII from the response.
     */
    public function present(Application $application): array
    {
        $payload = $application->toArray();
        $attributes = $application->getAttributes();

        $payload['customer_name'] = $this->customerPiiPresenter
            ->presentPlaintextValue($attributes['customer_name'] ?? null);

        unset($payload['customer_gender'], $payload['customer_contact']);

        return $payload;
    }
}
