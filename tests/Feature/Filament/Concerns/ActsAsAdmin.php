<?php

namespace Tests\Feature\Filament\Concerns;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Spatie\Permission\Models\Role;

trait ActsAsAdmin
{
    protected User $adminUser;

    protected function setUpActsAsAdmin(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('super_admin');
    }

    protected function actingAsAdmin(): static
    {
        return $this->actingAs($this->adminUser);
    }
}
