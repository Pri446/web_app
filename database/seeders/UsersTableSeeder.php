<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Deployment\SeedUserConfigurationValidator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /** Provision users without changing credentials for existing accounts. */
    public function run(SeedUserConfigurationValidator $validator): void
    {
        $accounts = $validator->validateOrFail(config('seed_users.accounts', []));

        DB::transaction(function () use ($accounts): void {
            foreach ($accounts as $account) {
                $user = User::where('username', $account['username'])->first();

                if (! $user) {
                    $user = new User();
                    $user->name = $account['name'];
                    $user->gender = '';
                    $user->username = $account['username'];
                    $user->email = $account['email'];
                    $user->password = Hash::make($account['password']);
                    $user->user_type = $account['user_type'];
                    $user->save();
                }

                if (! $user->hasRole($account['role'])) {
                    $user->assignRole($account['role']);
                }
            }
        });
    }
}
