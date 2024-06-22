<?php

namespace Tests\Traits;

use App\Enums\Roles;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

trait ActingAs
{
    public function actingAsSuperAdmin(): User
    {

        $user = User::factory()->create(['role' => Roles::SUPER_ADMIN->value]);

        $role = strtolower(Roles::SUPER_ADMIN->value);

        Sanctum::actingAs($user, ["role:$role"]);

        return $user;
    }

    public function actingAsAdmin(): User
    {

        $user = User::factory()->create(['role' => Roles::ADMIN->value]);

        $role = strtolower(Roles::ADMIN->value);

        Sanctum::actingAs($user, ["role:$role"]);

        return $user;
    }
}
