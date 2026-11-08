<?php

namespace Tests\Feature\Fsm\Concerns;

use App\Models\Fsm\ServiceProvider;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait CreatesDesludgingTestData
{
    protected function createDesludgingUser(): User
    {
        $token = Str::lower(Str::random(12));
        $user = new User();
        $user->name = 'Desludging PHPUnit User';
        $user->username = 'desludging-' . $token;
        $user->email = $token . '@example.test';
        $user->password = 'not-used';
        $user->user_type = 'Municipality';
        $user->status = 1;
        $user->save();

        return $user;
    }

    protected function createDesludgingProvider(int $ward): ServiceProvider
    {
        $token = Str::lower(Str::random(12));
        $provider = new ServiceProvider();
        $provider->company_name = 'Desludging Provider ' . $token;
        $provider->email = $token . '@example.test';
        $provider->ward = $ward;
        $provider->company_location = 'Test Location';
        $provider->contact_person = 'Test Contact';
        $provider->contact_gender = 'Male';
        $provider->contact_number = '9800000000';
        $provider->service_area = (string) $ward;
        $provider->status = 1;
        $provider->save();

        DB::table('fsm.service_provider_wards')->insert([
            'service_provider_id' => $provider->id,
            'ward' => $ward,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $provider;
    }

    protected function createScheduledProperty(
        ?ServiceProvider $provider = null,
        int $containmentStatus = 0
    ): array {
        $token = Str::upper(Str::random(10));
        // The legacy owners table limits BIN values to seven characters.
        $bin = 'T' . substr($token, 0, 6);
        $containmentId = 'TEST-CONT-' . $token;
        $roadCode = 'TEST-ROAD-' . $token;
        $ward = $provider ? (int) $provider->ward : 7;

        DB::table('utility_info.roads')->insert([
            'code' => $roadCode,
            'name' => 'PHPUnit Road',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('building_info.buildings')->insert([
            'bin' => $bin,
            'ward' => $ward,
            'road_code' => $roadCode,
            'house_number' => 'TEST-HOUSE',
            'house_locality' => 'Test Locality',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('building_info.owners')->insert([
            'bin' => $bin,
            'owner_name' => 'Test Owner',
            'owner_gender' => 'Male',
            'owner_contact' => 9800000000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('fsm.containments')->insert([
            'id' => $containmentId,
            'status' => $containmentStatus,
            'emptied_status' => false,
            'priority' => 1,
            'fstp_distance' => 1.5,
            'next_emptying_date' => now()->addDay()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('building_info.build_contains')->insert([
            'bin' => $bin,
            'containment_id' => $containmentId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('fsm.desludging_schedule_temp')->insert([
            'service_provider_id' => $provider ? $provider->id : null,
            'service_provider_name' => $provider
                ? $provider->company_name
                : null,
            'bin' => $bin,
            'ward' => $ward,
            'house_number' => 'TEST-HOUSE',
            'house_locality' => 'Test Locality',
            'road_code' => $roadCode,
            'containment_id' => $containmentId,
            'next_emptying_date' => now()->addDay()->toDateString(),
            'fstp_distance' => 1.5,
            'priority' => 1,
            'sequence' => 1,
            'status' => $containmentStatus,
            'owner_name' => 'Test Owner',
            'owner_gender' => 'Male',
            'owner_contact' => '9800000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return compact('bin', 'containmentId', 'roadCode', 'ward');
    }

    protected function confirmedApplicationPayload(
        array $property,
        ServiceProvider $provider
    ): array {
        return [
            'action_type' => 'confirm',
            'road_code' => $property['roadCode'],
            'bin' => $property['bin'],
            'ward' => $property['ward'],
            'containment_id' => $property['containmentId'],
            'customer_name' => 'Test Owner',
            'customer_gender' => 'Male',
            'customer_contact' => 9800000000,
            'applicant_name' => 'Test Applicant',
            'applicant_gender' => 'Male',
            'applicant_contact' => 9811111111,
            'proposed_emptying_date' => now()->addDay()->toDateString(),
            'service_provider_id' => $provider->id,
            'emergency_desludging_status' => false,
            'desludging_vehicle_size' => 5,
        ];
    }
}
