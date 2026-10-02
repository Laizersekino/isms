<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\ClassRoom;
use App\Models\ParentModel;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Stream;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $this->get(route('announcements.index'))
            ->assertRedirect(route('login'));
    }

    public function test_all_authenticated_roles_can_view_announcements(): void
    {
        $role = Role::create(['name' => 'Student']);
        $user = User::factory()->create();
        $permission = Permission::firstOrCreate(['name' => 'announcements.view']);
        $role->permissions()->attach($permission->id);
        $user->roles()->attach($role);
        $announcement = $this->createAnnouncement([
            'audience_type' => 'all',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('announcements.index'))
            ->assertOk()
            ->assertSee($announcement->title);

        $this->get(route('announcements.show', $announcement))
            ->assertOk()
            ->assertSee($announcement->content);
    }

    public function test_user_without_view_permission_cannot_view_announcements(): void
    {
        $user = $this->userWithPermissions([]);

        $this->actingAs($user)
            ->get(route('announcements.index'))
            ->assertForbidden();
    }

    public function test_user_without_create_permission_cannot_create_announcements(): void
    {
        $user = $this->userWithPermissions(['announcements.view']);

        $this->actingAs($user)
            ->get(route('announcements.create'))
            ->assertForbidden();

        $this->post(route('announcements.store'), $this->validPayload())
            ->assertForbidden();
    }

    public function test_create_permission_stores_announcement_as_draft_owned_by_user(): void
    {
        $user = $this->userWithPermissions(['announcements.create', 'announcements.view']);

        $response = $this->actingAs($user)
            ->post(route('announcements.store'), $this->validPayload([
                'title' => 'Parent meeting',
                'content' => 'The meeting begins at 10:00.',
                'is_pinned' => '1',
            ]));

        $announcement = Announcement::query()->sole();
        $response->assertRedirect(route('announcements.show', $announcement));
        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'title' => 'Parent meeting',
            'status' => 'draft',
            'created_by' => $user->id,
            'is_pinned' => true,
        ]);
    }

    public function test_invalid_announcement_fields_are_rejected(): void
    {
        $user = $this->userWithPermissions(['announcements.create']);

        $this->actingAs($user)
            ->from(route('announcements.create'))
            ->post(route('announcements.store'), [
                'title' => '',
                'content' => '',
                'category' => 'invalid',
                'audience_type' => 'role',
                'audience_value' => [],
                'publish_at' => now()->subMinute()->toDateTimeString(),
            ])
            ->assertSessionHasErrors([
                'title',
                'content',
                'category',
                'audience_value',
                'publish_at',
            ]);

        $this->assertSame(0, Announcement::query()->count());
    }

    public function test_expiration_must_follow_publish_time(): void
    {
        $user = $this->userWithPermissions(['announcements.create']);
        $publishAt = now()->addHours(2);

        $this->actingAs($user)
            ->from(route('announcements.create'))
            ->post(route('announcements.store'), $this->validPayload([
                'publish_at' => $publishAt->toDateTimeString(),
                'expires_at' => $publishAt->subMinute()->toDateTimeString(),
            ]))
            ->assertSessionHasErrors('expires_at');
    }

    public function test_creator_can_update_draft_but_another_user_cannot(): void
    {
        $creator = $this->userWithPermissions(['announcements.update', 'announcements.view']);
        $otherUser = $this->userWithPermissions(['announcements.update']);
        $announcement = $this->createAnnouncement([
            'created_by' => $creator->id,
            'status' => 'draft',
        ]);

        $this->actingAs($otherUser)
            ->put(route('announcements.update', $announcement), $this->validPayload([
                'title' => 'Unauthorized change',
            ]))
            ->assertForbidden();

        $this->actingAs($creator)
            ->put(route('announcements.update', $announcement), $this->validPayload([
                'title' => 'Updated draft',
            ]))
            ->assertRedirect(route('announcements.show', $announcement));

        $this->assertSame('Updated draft', $announcement->fresh()->title);
    }

    public function test_non_owner_cannot_view_another_users_draft(): void
    {
        $user = $this->userWithPermissions(['announcements.view']);
        $announcement = $this->createAnnouncement(['status' => 'draft']);

        $this->actingAs($user)
            ->get(route('announcements.show', $announcement))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('announcements.index'))
            ->assertDontSee($announcement->title);
    }

    public function test_role_audience_only_shows_announcement_to_matching_role(): void
    {
        $targetRole = Role::create(['name' => 'Parent']);
        $this->grantViewPermission($targetRole);
        $targetUser = User::factory()->create();
        $targetUser->roles()->attach($targetRole);
        $otherRole = Role::create(['name' => 'Other Audience']);
        $this->grantViewPermission($otherRole);
        $otherUser = User::factory()->create();
        $otherUser->roles()->attach($otherRole);
        $announcement = $this->createAnnouncement([
            'audience_type' => 'role',
            'audience_value' => [$targetRole->id],
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->actingAs($targetUser)
            ->get(route('announcements.index'))
            ->assertSee($announcement->title);

        $this->actingAs($otherUser)
            ->get(route('announcements.index'))
            ->assertDontSee($announcement->title);
    }

    public function test_class_audience_is_visible_to_student_parent_and_assigned_teacher(): void
    {
        $context = $this->createClassContext();
        $student = Student::create([
            'admission_number' => 'ANN-001',
            'first_name' => 'Class',
            'last_name' => 'Student',
            'email' => 'student-announcement@example.test',
            'date_of_birth' => '2010-01-01',
            'gender' => 'Other',
            'status' => 'active',
        ]);
        StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $context['year']->id,
            'class_id' => $context['class']->id,
            'status' => 'active',
        ]);
        $studentRole = Role::create(['name' => 'Student']);
        $this->grantViewPermission($studentRole);
        $studentUser = User::factory()->create(['email' => $student->email]);
        $studentUser->roles()->attach($studentRole);

        $parent = new ParentModel;
        $parent->first_name = 'Class';
        $parent->last_name = 'Parent';
        $parent->phone = '555-0101';
        $parent->email = 'parent-announcement@example.test';
        $parent->save();
        $student->parents()->attach($parent->id);
        $parentRole = Role::create(['name' => 'Parent']);
        $this->grantViewPermission($parentRole);
        $parentUser = User::factory()->create(['email' => $parent->email]);
        $parentUser->roles()->attach($parentRole);

        $teacher = Teacher::create([
            'employee_number' => 'ANN-T-001',
            'first_name' => 'Class',
            'last_name' => 'Teacher',
            'status' => 'active',
        ]);
        $teacherUser = User::factory()->create();
        $teacherUser->teacher_id = $teacher->id;
        $teacherUser->save();
        $teacherRole = Role::create(['name' => 'Teacher']);
        $this->grantViewPermission($teacherRole);
        $teacherUser->roles()->attach($teacherRole);
        $stream = Stream::create([
            'class_id' => $context['class']->id,
            'name' => 'A',
            'status' => 'active',
        ]);
        $subject = Subject::create([
            'name' => 'Announcement Test Subject',
            'code' => 'ANN-SUB-001',
            'status' => 'active',
        ]);
        TeacherAssignment::create([
            'teacher_id' => $teacher->id,
            'class_id' => $context['class']->id,
            'stream_id' => $stream->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $context['year']->id,
            'status' => 'active',
        ]);
        $announcement = $this->createAnnouncement([
            'audience_type' => 'class',
            'audience_value' => [$context['class']->id],
            'status' => 'published',
            'published_at' => now(),
        ]);

        foreach ([$studentUser, $parentUser, $teacherUser] as $targetUser) {
            $this->actingAs($targetUser)
                ->get(route('announcements.index'))
                ->assertSee($announcement->title);
        }
    }

    public function test_specific_user_audience_is_hidden_from_other_users(): void
    {
        $targetUser = $this->userWithPermissions(['announcements.view']);
        $otherUser = $this->userWithPermissions(['announcements.view']);
        $announcement = $this->createAnnouncement([
            'audience_type' => 'specific_users',
            'audience_value' => [$targetUser->id],
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->actingAs($targetUser)
            ->get(route('announcements.show', $announcement))
            ->assertOk()
            ->assertSee($announcement->title);

        $this->actingAs($otherUser)
            ->get(route('announcements.show', $announcement))
            ->assertNotFound();
    }

    public function test_dashboard_shows_recent_visible_announcement_as_in_app_notification(): void
    {
        $user = $this->userWithPermissions(['announcements.view']);
        $announcement = $this->createAnnouncement([
            'title' => 'Dashboard notice',
            'status' => 'published',
            'published_at' => now(),
            'is_pinned' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Recent Announcements')
            ->assertSee('Dashboard notice')
            ->assertSee(route('announcements.show', $announcement));
    }

    public function test_draft_scheduled_and_expired_announcements_are_hidden_from_regular_users(): void
    {
        $user = $this->userWithPermissions(['announcements.view']);
        $draft = $this->createAnnouncement(['title' => 'Draft Hidden']);
        $scheduled = $this->createAnnouncement([
            'title' => 'Scheduled Hidden',
            'status' => 'published',
            'published_at' => now(),
            'publish_at' => now()->addHour(),
        ]);
        $expired = $this->createAnnouncement([
            'title' => 'Expired Hidden',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'expires_at' => now()->subMinute(),
        ]);

        $this->actingAs($user)
            ->get(route('announcements.index'))
            ->assertDontSee($draft->title)
            ->assertDontSee($scheduled->title)
            ->assertDontSee($expired->title);
    }

    public function test_archived_announcement_is_hidden_from_regular_users_and_visible_to_admins(): void
    {
        $announcement = $this->createAnnouncement([
            'status' => 'archived',
            'published_at' => now()->subDay(),
        ]);
        $regularUser = $this->userWithPermissions(['announcements.view']);
        $admin = $this->userWithRolePermissions('School Administrator', [
            'announcements.view',
            'announcements.delete',
        ]);

        $this->actingAs($regularUser)
            ->get(route('announcements.index'))
            ->assertDontSee($announcement->title);

        $this->actingAs($admin)
            ->get(route('announcements.index'))
            ->assertSee($announcement->title);
    }

    public function test_viewing_published_announcement_marks_read_once_and_counts_reads_for_admins(): void
    {
        $reader = $this->userWithPermissions(['announcements.view']);
        $admin = $this->userWithRolePermissions('School Administrator', ['announcements.view']);
        $announcement = $this->createAnnouncement([
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->actingAs($reader)
            ->get(route('announcements.show', $announcement))
            ->assertOk();
        $this->get(route('announcements.show', $announcement))->assertOk();

        $this->assertSame(1, AnnouncementRead::query()
            ->where('announcement_id', $announcement->id)
            ->where('user_id', $reader->id)
            ->count());

        $this->actingAs($admin)
            ->get(route('announcements.show', $announcement))
            ->assertSee('Read count:</strong> 2', false);
    }

    public function test_only_authorized_user_can_publish_draft_and_duplicate_publish_is_rejected(): void
    {
        $publisher = $this->userWithPermissions(['announcements.publish', 'announcements.view']);
        $announcement = $this->createAnnouncement();

        $this->actingAs($publisher)
            ->post(route('announcements.publish', $announcement))
            ->assertRedirect(route('announcements.show', $announcement));

        $this->assertSame('published', $announcement->fresh()->status);
        $this->assertSame($publisher->id, $announcement->fresh()->published_by);
        $this->assertNotNull($announcement->fresh()->published_at);

        $this->post(route('announcements.publish', $announcement))
            ->assertSessionHasErrors('announcement');
    }

    public function test_scheduled_draft_cannot_be_published_before_scheduled_time(): void
    {
        $publisher = $this->userWithPermissions(['announcements.publish']);
        $announcement = $this->createAnnouncement(['publish_at' => now()->addHour()]);

        $this->actingAs($publisher)
            ->from(route('announcements.show', $announcement))
            ->post(route('announcements.publish', $announcement))
            ->assertSessionHasErrors('publish_at');

        $this->assertSame('draft', $announcement->fresh()->status);
    }

    public function test_only_published_announcement_can_be_archived(): void
    {
        $publisher = $this->userWithPermissions(['announcements.publish']);
        $draft = $this->createAnnouncement();
        $published = $this->createAnnouncement([
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->actingAs($publisher)
            ->post(route('announcements.archive', $draft))
            ->assertSessionHasErrors('announcement');

        $this->post(route('announcements.archive', $published))
            ->assertRedirect(route('announcements.show', $published));

        $this->assertSame('archived', $published->fresh()->status);
    }

    public function test_admin_can_soft_delete_recent_announcement_but_old_published_announcement_is_protected(): void
    {
        $admin = $this->userWithRolePermissions('School Administrator', [
            'announcements.view',
            'announcements.delete',
        ]);
        $recent = $this->createAnnouncement(['status' => 'draft']);
        $oldPublished = $this->createAnnouncement([
            'status' => 'published',
            'published_at' => now()->subHours(25),
        ]);

        $this->actingAs($admin)
            ->delete(route('announcements.destroy', $recent))
            ->assertRedirect(route('announcements.index'));
        $this->assertSoftDeleted($recent);

        $this->delete(route('announcements.destroy', $oldPublished))
            ->assertSessionHasErrors('announcement');
        $this->assertNotSoftDeleted($oldPublished);
    }

    public function test_published_announcement_is_read_only_after_24_hours(): void
    {
        $this->travelTo('2026-10-02 14:00:00');
        $owner = $this->userWithPermissions(['announcements.view', 'announcements.update']);
        $admin = $this->userWithRolePermissions('School Administrator', [
            'announcements.view',
            'announcements.update',
        ]);
        $announcement = $this->createAnnouncement([
            'created_by' => $owner->id,
            'status' => 'published',
            'published_at' => now()->subHours(25),
        ]);

        $this->actingAs($owner)
            ->get(route('announcements.edit', $announcement))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('announcements.edit', $announcement))
            ->assertForbidden();
    }

    public function test_principal_can_update_owned_announcement_but_academic_officer_cannot(): void
    {
        $principal = $this->userWithRolePermissions('Principal', [
            'announcements.view',
            'announcements.update',
        ]);
        $academicOfficer = $this->userWithRolePermissions('Academic Officer', [
            'announcements.view',
            'announcements.create',
        ]);
        $announcement = $this->createAnnouncement([
            'created_by' => $principal->id,
            'status' => 'draft',
        ]);

        $this->actingAs($principal)
            ->put(route('announcements.update', $announcement), $this->validPayload([
                'title' => 'Principal updated draft',
            ]))
            ->assertRedirect(route('announcements.show', $announcement));

        $this->actingAs($academicOfficer)
            ->put(route('announcements.update', $announcement), $this->validPayload([
                'title' => 'Academic Officer cannot update',
            ]))
            ->assertForbidden();
    }

    public function test_announcement_permission_migration_grants_requested_roles(): void
    {
        $roleNames = [
            'Super Administrator',
            'School Administrator',
            'Principal',
            'Academic Officer',
            'Finance Officer',
            'Teacher',
            'Librarian',
            'Receptionist',
            'Parent',
            'Student',
        ];
        $roles = collect($roleNames)->mapWithKeys(fn (string $name): array => [
            $name => Role::create(['name' => $name]),
        ]);

        $migration = require database_path(
            'migrations/2026_10_02_140200_add_announcements_permissions.php'
        );
        $migration->up();

        foreach ($roleNames as $roleName) {
            $this->assertTrue(
                $roles[$roleName]->fresh()->permissions()->where('name', 'announcements.view')->exists()
            );
        }

        $this->assertSame(
            ['announcements.create', 'announcements.publish', 'announcements.update', 'announcements.view'],
            $roles['Principal']->fresh()->permissions()
                ->where('name', 'like', 'announcements.%')
                ->orderBy('name')
                ->pluck('name')
                ->all()
        );
        $this->assertSame(
            ['announcements.create', 'announcements.view'],
            $roles['Academic Officer']->fresh()->permissions()
                ->where('name', 'like', 'announcements.%')
                ->orderBy('name')
                ->pluck('name')
                ->all()
        );
        $this->assertSame(
            [
                'announcements.create',
                'announcements.delete',
                'announcements.publish',
                'announcements.update',
                'announcements.view',
            ],
            $roles['School Administrator']->fresh()->permissions()
                ->where('name', 'like', 'announcements.%')
                ->orderBy('name')
                ->pluck('name')
                ->all()
        );
    }

    private function userWithPermissions(array $permissionNames): User
    {
        return $this->userWithRolePermissions('Test Announcement Role', $permissionNames);
    }

    private function grantViewPermission(Role $role): void
    {
        $permission = Permission::firstOrCreate(['name' => 'announcements.view']);
        $role->permissions()->attach($permission->id);
    }

    private function userWithRolePermissions(string $roleName, array $permissionNames): User
    {
        static $testRoleIndex = 0;
        if ($roleName === 'Test Announcement Role') {
            $testRoleIndex++;
            $roleName .= ' '.$testRoleIndex;
        }

        $user = User::factory()->create();
        $role = Role::create(['name' => $roleName]);

        foreach ($permissionNames as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $role->permissions()->attach($permission->id);
        }

        $user->roles()->attach($role->id);

        return $user;
    }

    private function createAnnouncement(array $overrides = []): Announcement
    {
        static $index = 0;
        $index++;

        $creator = User::query()->find($overrides['created_by'] ?? null)
            ?? User::factory()->create();

        return Announcement::create(array_replace([
            'title' => 'Announcement '.$index,
            'content' => 'Announcement content '.$index,
            'category' => 'general',
            'audience_type' => 'all',
            'audience_value' => null,
            'publish_at' => null,
            'expires_at' => null,
            'status' => 'draft',
            'is_pinned' => false,
            'created_by' => $creator->id,
            'published_by' => null,
            'published_at' => null,
        ], $overrides));
    }

    private function validPayload(array $overrides = []): array
    {
        return array_replace([
            'title' => 'School announcement',
            'content' => 'Important information for the school community.',
            'category' => 'general',
            'audience_type' => 'all',
            'audience_value' => [],
            'is_pinned' => '0',
        ], $overrides);
    }

    /**
     * @return array{year: AcademicYear, term: Term, class: ClassRoom}
     */
    private function createClassContext(): array
    {
        $year = AcademicYear::create([
            'name' => 'Announcement Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_current' => false,
            'status' => 'active',
        ]);
        $term = Term::create([
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);
        $class = ClassRoom::create([
            'name' => 'Announcement Class',
            'description' => null,
            'status' => 'active',
        ]);

        return compact('year', 'term', 'class');
    }
}
