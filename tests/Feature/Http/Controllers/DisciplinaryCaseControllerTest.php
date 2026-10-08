<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\DisciplinaryCase;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisciplinaryCaseControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithPermission(string $permission): User
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'Test Role']);
        $perm = Permission::firstOrCreate(['name' => $permission]);
        $role->permissions()->syncWithoutDetaching([$perm->id]);
        $user->roles()->attach($role->id);

        return $user;
    }

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $response = $this->get(route('discipline.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_user_without_view_permission_receives_forbidden(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('discipline.index'));
        $response->assertForbidden();
    }

    public function test_user_with_view_permission_can_see_index(): void
    {
        $user = $this->createUserWithPermission('discipline.view');
        $response = $this->actingAs($user)->get(route('discipline.index'));
        $response->assertOk();
    }

    public function test_user_with_create_permission_can_see_create_form(): void
    {
        $user = $this->createUserWithPermission('discipline.create');
        $response = $this->actingAs($user)->get(route('discipline.create'));
        $response->assertOk();
    }

    public function test_user_with_create_permission_can_store_case(): void
    {
        $user = $this->createUserWithPermission('discipline.create');
        $student = Student::factory()->create();

        $response = $this->actingAs($user)->post(route('discipline.store'), [
            'student_id' => $student->id,
            'offence_type' => 'Late arrival',
            'incident_date' => '2026-10-08',
            'description' => 'Student arrived late to class.',
            'status' => 'open',
        ]);

        $response->assertRedirect(route('discipline.index'));
        $this->assertDatabaseHas('disciplinary_cases', [
            'student_id' => $student->id,
            'offence_type' => 'Late arrival',
            'status' => 'open',
        ]);
    }

    public function test_store_requires_validation(): void
    {
        $user = $this->createUserWithPermission('discipline.create');

        $response = $this->actingAs($user)->post(route('discipline.store'), []);
        $response->assertSessionHasErrors(['student_id', 'offence_type', 'incident_date', 'description', 'status']);
    }

    public function test_user_with_view_permission_can_see_case(): void
    {
        $user = $this->createUserWithPermission('discipline.view');
        $case = DisciplinaryCase::factory()->create();

        $response = $this->actingAs($user)->get(route('discipline.show', $case));
        $response->assertOk();
        $response->assertSee($case->offence_type);
    }

    public function test_user_with_update_permission_can_update_case(): void
    {
        $user = $this->createUserWithPermission('discipline.update');
        $case = DisciplinaryCase::factory()->create();
        $student = Student::factory()->create();

        $response = $this->actingAs($user)->put(route('discipline.update', $case), [
            'student_id' => $student->id,
            'offence_type' => 'Updated offence',
            'incident_date' => '2026-10-08',
            'description' => 'Updated description.',
            'status' => 'resolved',
        ]);

        $response->assertRedirect(route('discipline.show', $case));
        $this->assertDatabaseHas('disciplinary_cases', [
            'id' => $case->id,
            'offence_type' => 'Updated offence',
            'status' => 'resolved',
        ]);
    }

    public function test_user_with_delete_permission_can_delete_case(): void
    {
        $user = $this->createUserWithPermission('discipline.delete');
        $case = DisciplinaryCase::factory()->create();

        $response = $this->actingAs($user)->delete(route('discipline.destroy', $case));
        $response->assertRedirect(route('discipline.index'));
        $this->assertSoftDeleted('disciplinary_cases', ['id' => $case->id]);
    }

    public function test_user_without_delete_permission_cannot_delete_case(): void
    {
        $user = User::factory()->create();
        $case = DisciplinaryCase::factory()->create();

        $response = $this->actingAs($user)->delete(route('discipline.destroy', $case));
        $response->assertForbidden();
    }

    public function test_index_filters_by_status(): void
    {
        $user = $this->createUserWithPermission('discipline.view');
        DisciplinaryCase::factory()->create(['status' => 'open']);
        DisciplinaryCase::factory()->create(['status' => 'resolved']);

        $response = $this->actingAs($user)->get(route('discipline.index', ['status' => 'open']));
        $response->assertOk();
    }
}