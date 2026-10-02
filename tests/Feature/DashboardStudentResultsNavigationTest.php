<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStudentResultsNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_and_parent_portal_roles_see_the_student_results_link(): void
    {
        foreach (['Student', 'Parent'] as $roleName) {
            $user = $this->userWithRole($roleName);

            $response = $this->actingAs($user)->get(route('dashboard'));

            $response->assertSee(route('student-results.index'));
            $response->assertSee('Student Results');
        }
    }

    public function test_portal_permissions_see_the_student_results_link(): void
    {
        foreach (['student.portal', 'parent.portal'] as $permissionName) {
            $user = $this->userWithPermission($permissionName);

            $response = $this->actingAs($user)->get(route('dashboard'));

            $response->assertSee(route('student-results.index'));
            $response->assertSee('Student Results');
        }
    }

    public function test_staff_results_permissions_see_the_student_results_link(): void
    {
        foreach (['students.view', 'reports.view', 'marks.view'] as $permissionName) {
            $user = $this->userWithPermission($permissionName);

            $response = $this->actingAs($user)->get(route('dashboard'));

            $response->assertSee(route('student-results.index'));
            $response->assertSee('Student Results');
        }
    }

    public function test_user_without_student_result_access_does_not_see_the_link(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertDontSee(route('student-results.index'));
        $response->assertDontSee('Student Results');
    }

    private function userWithRole(string $roleName): User
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => $roleName]);
        $user->roles()->attach($role->id);

        return $user;
    }

    private function userWithPermission(string $permissionName): User
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Role for '.$permissionName]);
        $permission = Permission::create(['name' => $permissionName]);
        $role->permissions()->attach($permission->id);
        $user->roles()->attach($role->id);

        return $user;
    }
}
