<?php

namespace App\Services\Api;

use App\Models\BuildingInfo\Building;
use App\Services\OwnerPiiPresenter;

class MobileBuildingPresenter
{
    private OwnerPiiPresenter $ownerPiiPresenter;

    public function __construct(OwnerPiiPresenter $ownerPiiPresenter)
    {
        $this->ownerPiiPresenter = $ownerPiiPresenter;
    }

    /**
     * Preserve the existing mobile Building payload while replacing the
     * eagerly-loaded Owner model with the only owner field approved for the
     * temporary mobile policy: the server-decrypted owner name.
     */
    public function present(Building $building): array
    {
        $payload = $building->toArray();
        $owner = $building->Owners;

        $payload['owners'] = $owner
            ? [
                'owner_name' => $this->ownerPiiPresenter
                    ->presentPlaintextValue(
                        $owner->getAttributes()['owner_name'] ?? null
                    ),
            ]
            : null;

        return $payload;
    }

    public function ownerName(Building $building): ?string
    {
        $owner = $building->Owners;

        if (!$owner) {
            return null;
        }

        return $this->ownerPiiPresenter->presentPlaintextValue(
            $owner->getAttributes()['owner_name'] ?? null
        );
    }
}
