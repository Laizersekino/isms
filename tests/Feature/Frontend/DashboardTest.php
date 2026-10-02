<?php

namespace Tests\Feature\Frontend;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_dashboard_loads_for_all_ten_school_roles(): void
    {
        $rolePermissions = [
            'Super Administrator' => $this->allNavigationPermissions(),
            'School Administrator' => $this->allNavigationPermissions(),
            'Principal' => ['students.view', 'teachers.view', 'fee_structures.view', 'reports.academic.view', 'announcements.view'],
            'Academic Officer' => ['academic_structure.update', 'attendance.view', 'marks.view', 'reports.academic.view', 'announcements.view'],
            'Teacher' => ['attendance.view', 'marks.view', 'books.view', 'borrowings.view', 'announcements.view'],
            'Librarian' => ['books.view', 'book_copies.view', 'borrowings.view', 'fines.view', 'announcements.view'],
            'Finance Officer' => ['fee_structures.view', 'student_fees.view', 'payments.view', 'reports.finance.view', 'announcements.view'],
            'Receptionist' => ['students.view', 'announcements.view'],
            'Parent' => ['announcements.view'],
            'Student' => ['announcements.view'],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            $user = $this->userWithRolePermissions($roleName, $permissions);

            $response = $this->actingAs($user)->get(route('dashboard'));

            $response->assertOk()
                ->assertSee($user->name)
                ->assertSee($roleName);
            if (in_array($roleName, ['Parent', 'Student'], true)) {
                $response->assertSee('Student Results');
            }
        }
    }

    public function test_sidebar_uses_route_permissions_for_role_navigation(): void
    {
        $librarian = $this->userWithRolePermissions('Librarian', [
            'books.view',
            'book_copies.view',
            'borrowings.view',
            'fines.view',
            'announcements.view',
        ]);

        $this->actingAs($librarian)
            ->get(route('dashboard'))
            ->assertSee('Books')
            ->assertSee('Book copies')
            ->assertSee('Borrowings')
            ->assertSee('Fines')
            ->assertDontSee('Payments')
            ->assertDontSee('Students');
    }

    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_dashboard_renders_flash_messages(): void
    {
        $user = $this->userWithRolePermissions('School Administrator', []);

        $this->actingAs($user)
            ->withSession(['success' => 'Changes saved successfully.'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Changes saved successfully.')
            ->assertSee('role="status"', false);
    }

    public function test_authenticated_user_can_view_and_update_own_profile(): void
    {
        $user = $this->userWithRolePermissions('Student', []);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Your profile')
            ->assertSee($user->email);

        $this->put(route('profile.update'), [
            'name' => 'Updated Student',
            'email' => 'updated.student@example.test',
        ])->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success', 'Profile updated successfully.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Student',
            'email' => 'updated.student@example.test',
        ]);
    }

    /**
     * @return list<string>
     */
    private function allNavigationPermissions(): array
    {
        return [
            'students.view',
            'teachers.view',
            'academic_structure.update',
            'attendance.view',
            'marks.view',
            'reports.academic.view',
            'books.view',
            'book_copies.view',
            'borrowings.view',
            'fines.view',
            'fee_structures.view',
            'student_fees.view',
            'payments.view',
            'reports.finance.view',
            'announcements.view',
        ];
    }

    private function userWithRolePermissions(string $roleName, array $permissionNames): User
    {
        $user = User::factory()->create(['name' => $roleName.' User']);
        $role = Role::create(['name' => $roleName]);

        foreach (array_unique($permissionNames) as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $role->permissions()->attach($permission->id);
        }

        $user->roles()->attach($role->id);

        return $user;
    }
}
