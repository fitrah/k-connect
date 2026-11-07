<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use App\Support\OrganizationContext;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $organization = Organization::query()->updateOrCreate(
            ['slug' => 'k-connect-demo'],
            ['name' => 'K-Connect Demo'],
        );
        $context = app(OrganizationContext::class);
        $context->set($organization);

        try {
            User::query()->updateOrCreate(
                ['email' => config('k-connect.demo_admin_email')],
                [
                    'organization_id' => $organization->id,
                    'name' => 'K-Connect Administrator',
                    'password' => config('k-connect.demo_admin_password'),
                    'role' => 'administrator',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );
        } finally {
            $context->clear();
        }
    }
}
