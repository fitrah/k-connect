<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Support\OrganizationContext;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_development_seeder_creates_a_demo_organization_and_administrator(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $organization = Organization::query()->where('slug', 'k-connect-demo')->firstOrFail();
        $this->assertSame(1, Organization::query()->where('slug', 'k-connect-demo')->count());

        app(OrganizationContext::class)->set($organization);
        $administrator = User::query()->where('email', config('k-connect.demo_admin_email'))->firstOrFail();

        $this->assertSame(1, User::query()->where('email', config('k-connect.demo_admin_email'))->count());
        $this->assertTrue($administrator->organization->is($organization));
        $this->assertSame('administrator', $administrator->role);
        $this->assertTrue($administrator->is_active);
        $this->assertTrue(Hash::check(config('k-connect.demo_admin_password'), $administrator->password));
    }
}
