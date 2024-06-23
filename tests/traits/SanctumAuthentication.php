<?php

namespace Tests\Traits;

use App\Enums\Roles;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * @author @Intuneteq
 *
 * Trait for setting up authenticated users with specific roles using Sanctum.
 *
 * This trait provides helper methods to authenticate as users with specific roles
 * using Laravel Sanctum. It supports roles defined in the Roles enum.
 */
trait SanctumAuthentication
{
    /**
     * Authenticate as a super admin user.
     *
     * @return User The authenticated super admin user instance.
     */
    public function actingAsSuperAdmin(): User
    {
        $user = User::factory()->create(['role' => Roles::SUPER_ADMIN->value]);
        $role = strtolower(Roles::SUPER_ADMIN->value);
        Sanctum::actingAs($user, ["role:$role"]);
        return $user;
    }

    /**
     * Authenticate as an admin user.
     *
     * @return User The authenticated admin user instance.
     */
    public function actingAsAdmin(): User
    {
        $user = User::factory()->create(['role' => Roles::ADMIN->value]);
        $role = strtolower(Roles::ADMIN->value);
        Sanctum::actingAs($user, ["role:$role"]);
        return $user;
    }

    public function actingAsRegularUser(): User
    {
        $user = User::factory()->create(['role' => Roles::USER->value]);
        $role = strtolower(Roles::USER->value);
        Sanctum::actingAs($user, ["role:$role"]);
        return $user;
    }
}
