<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\FeeStructure;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentFee;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentFeeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $this->get(route('student-fees.index'))->assertRedirect(route('login'));
        $this->get(route('student-fees.generate-form'))->assertRedirect(route('login'));
    }

    public function test_user_without_view_permission_receives_forbidden_response(): void
    {
        $user = $this->userWithPermissions([]);

        $this->actingAs($user)
            ->get(route('student-fees.index'))
            ->assertForbidden();
    }

    public function test_view_permission_can_list_and_filter_student_fees(): void
    {
        $user = $this->userWithPermissions(['student_fees.view']);
        $context = $this->academicContext();
        $structure = $this->createFeeStructure($context);
        $firstStudent = $this->createStudent('S-001');
        $secondStudent = $this->createStudent('S-002');
        $firstFee = $this->createStudentFee($firstStudent, $structure, $structure->items->first());
        $this->createStudentFee($secondStudent, $structure, $structure->items->last());

        $this->actingAs($user)
            ->get(route('student-fees.index', [
                'student_id' => $firstStudent->id,
                'status' => 'unpaid',
            ]))
            ->assertOk()
            ->assertSee($firstFee->feeStructureItem->name)
            ->assertSee($firstStudent->admission_number)
            ->assertDontSee($secondStudent->admission_number);
    }

    public function test_view_permission_can_show_fee_details(): void
    {
        $user = $this->userWithPermissions(['student_fees.view']);
        $context = $this->academicContext();
        $structure = $this->createFeeStructure($context);
        $student = $this->createStudent('S-003');
        $fee = $this->createStudentFee($student, $structure, $structure->items->first());

        $this->actingAs($user)
            ->get(route('student-fees.show', $fee))
            ->assertOk()
            ->assertSee($student->admission_number)
            ->assertSee($structure->name)
            ->assertSee('Tuition')
            ->assertSee('TZS 50,000.00')
            ->assertSee('Payments');
    }

    public function test_generate_permission_can_open_the_generation_form(): void
    {
        $user = $this->userWithPermissions(['student_fees.generate']);
        $context = $this->academicContext();

        $this->actingAs($user)
            ->get(route('student-fees.generate-form'))
            ->assertOk()
            ->assertSee($context['year']->name)
            ->assertSee($context['term']->name)
            ->assertSee($context['class']->name);
    }

    public function test_generation_creates_one_fee_per_enrolled_student_and_structure_item(): void
    {
        $user = $this->userWithPermissions(['student_fees.generate']);
        $context = $this->academicContext();
        $structure = $this->createFeeStructure($context, ['status' => 'active']);
        $students = [
            $this->createStudent('S-004'),
            $this->createStudent('S-005'),
            $this->createStudent('S-006'),
        ];

        foreach ($students as $student) {
            $this->createEnrollment($student, $context);
        }

        $this->actingAs($user)
            ->post(route('student-fees.generate'), $this->generationPayload($context))
            ->assertRedirect(route('student-fees.index'))
            ->assertSessionHas('success', 'Student fees generated: 9 created, 0 skipped (already existed).');

        $this->assertDatabaseCount('student_fees', 9);
        $this->assertDatabaseHas('student_fees', [
            'student_id' => $students[0]->id,
            'fee_structure_id' => $structure->id,
            'fee_structure_item_id' => $structure->items->first()->id,
            'amount' => '50000.00',
            'paid_amount' => '0.00',
            'balance' => '50000.00',
            'status' => 'unpaid',
            'due_date' => $this->generationPayload($context)['due_date'],
            'created_by' => $user->id,
        ]);
    }

    public function test_generation_is_idempotent_and_reports_skipped_fees(): void
    {
        $user = $this->userWithPermissions(['student_fees.generate']);
        $context = $this->academicContext();
        $structure = $this->createFeeStructure($context, ['status' => 'active']);
        $student = $this->createStudent('S-007');
        $this->createEnrollment($student, $context);

        $this->actingAs($user)
            ->post(route('student-fees.generate'), $this->generationPayload($context))
            ->assertSessionHas('success', 'Student fees generated: 3 created, 0 skipped (already existed).');

        $this->post(route('student-fees.generate'), $this->generationPayload($context))
            ->assertSessionHas('success', 'Student fees generated: 0 created, 3 skipped (already existed).');

        $this->assertDatabaseCount('student_fees', 3);
        $this->assertSame(3, StudentFee::query()
            ->where('student_id', $student->id)
            ->where('fee_structure_id', $structure->id)
            ->count());
    }

    public function test_generation_requires_an_active_fee_structure(): void
    {
        $user = $this->userWithPermissions(['student_fees.generate']);
        $context = $this->academicContext();
        $this->createFeeStructure($context, ['status' => 'draft']);

        $this->actingAs($user)
            ->from(route('student-fees.generate-form'))
            ->post(route('student-fees.generate'), $this->generationPayload($context))
            ->assertSessionHasErrors('fee_structure');

        $this->assertDatabaseCount('student_fees', 0);
    }

    public function test_generation_rejects_a_term_from_another_academic_year(): void
    {
        $user = $this->userWithPermissions(['student_fees.generate']);
        $context = $this->academicContext();
        $otherYear = $this->createAcademicYear('2027');
        $otherTerm = $this->createTerm($otherYear);

        $this->actingAs($user)
            ->from(route('student-fees.generate-form'))
            ->post(route('student-fees.generate'), array_replace(
                $this->generationPayload($context),
                ['term_id' => $otherTerm->id]
            ))
            ->assertSessionHasErrors('term_id');
    }

    public function test_generation_rejects_a_due_date_before_today(): void
    {
        $user = $this->userWithPermissions(['student_fees.generate']);
        $context = $this->academicContext();

        $this->actingAs($user)
            ->from(route('student-fees.generate-form'))
            ->post(route('student-fees.generate'), $this->generationPayload($context, [
                'due_date' => today()->subDay()->toDateString(),
            ]))
            ->assertSessionHasErrors('due_date');
    }

    public function test_finance_officer_can_view_and_generate_but_cannot_delete(): void
    {
        $user = $this->userWithRolePermissions('Finance Officer', [
            'student_fees.view',
            'student_fees.generate',
        ]);
        $context = $this->academicContext();
        $structure = $this->createFeeStructure($context, ['status' => 'active']);
        $student = $this->createStudent('S-008');
        $this->createEnrollment($student, $context);

        $this->actingAs($user)
            ->get(route('student-fees.index'))
            ->assertOk();
        $this->post(route('student-fees.generate'), $this->generationPayload($context))
            ->assertRedirect(route('student-fees.index'));

        $fee = StudentFee::query()->firstOrFail();
        $this->delete(route('student-fees.destroy', $fee))->assertForbidden();
        $this->assertModelExists($fee);
    }

    public function test_user_without_generate_permission_cannot_generate_fees(): void
    {
        $user = $this->userWithPermissions(['student_fees.view']);
        $context = $this->academicContext();

        $this->actingAs($user)
            ->get(route('student-fees.generate-form'))
            ->assertForbidden();
        $this->post(route('student-fees.generate'), $this->generationPayload($context))
            ->assertForbidden();
    }

    public function test_user_without_delete_permission_cannot_delete_a_fee(): void
    {
        $user = $this->userWithPermissions(['student_fees.view']);
        $context = $this->academicContext();
        $structure = $this->createFeeStructure($context);
        $fee = $this->createStudentFee(
            $this->createStudent('S-009'),
            $structure,
            $structure->items->first()
        );

        $this->actingAs($user)
            ->delete(route('student-fees.destroy', $fee))
            ->assertForbidden();

        $this->assertModelExists($fee);
    }

    public function test_delete_permission_soft_deletes_a_fee_when_no_payment_records_exist(): void
    {
        $user = $this->userWithPermissions(['student_fees.delete']);
        $context = $this->academicContext();
        $structure = $this->createFeeStructure($context);
        $fee = $this->createStudentFee(
            $this->createStudent('S-010'),
            $structure,
            $structure->items->first()
        );

        $this->actingAs($user)
            ->delete(route('student-fees.destroy', $fee))
            ->assertRedirect(route('student-fees.index'))
            ->assertSessionHas('success', 'Student fee deleted successfully.');

        $this->assertSoftDeleted('student_fees', ['id' => $fee->id]);
    }

    public function test_fee_structure_cannot_be_archived_after_student_fees_are_generated(): void
    {
        $user = $this->userWithPermissions(['fee_structures.delete']);
        $context = $this->academicContext();
        $structure = $this->createFeeStructure($context);
        $this->createStudentFee(
            $this->createStudent('S-014'),
            $structure,
            $structure->items->first()
        );

        $this->actingAs($user)
            ->delete(route('fee-structures.destroy', $structure))
            ->assertSessionHasErrors('delete');

        $this->assertModelExists($structure);
        $this->assertNotSoftDeleted('fee_structures', ['id' => $structure->id]);
    }

    public function test_delete_is_blocked_when_payment_records_exist(): void
    {
        $user = $this->userWithPermissions(['student_fees.delete']);
        $context = $this->academicContext();
        $structure = $this->createFeeStructure($context);
        $fee = $this->createStudentFee(
            $this->createStudent('S-015'),
            $structure,
            $structure->items->first()
        );
        $payment = Payment::create([
            'student_fee_id' => $fee->id,
            'student_id' => $fee->student_id,
            'amount' => '1000.00',
            'payment_method' => 'cash',
            'receipt_number' => Payment::generateReceiptNumber(),
            'paid_date' => today()->toDateString(),
            'recorded_by' => User::factory()->create()->id,
            'status' => 'completed',
        ]);

        $this->actingAs($user)
            ->delete(route('student-fees.destroy', $fee))
            ->assertSessionHasErrors('delete');

        $this->assertModelExists($fee);
        $this->assertModelExists($payment);
        $this->assertNotSoftDeleted('student_fees', ['id' => $fee->id]);
    }

    public function test_student_fee_recalculates_balance_and_paid_status(): void
    {
        $context = $this->academicContext();
        $structure = $this->createFeeStructure($context);
        $fee = $this->createStudentFee(
            $this->createStudent('S-011'),
            $structure,
            $structure->items->first()
        );

        $fee->update(['paid_amount' => '12500.00']);
        $fee->recalculateBalance();

        $this->assertSame('37500.00', $fee->fresh()->balance);
        $this->assertSame('partially_paid', $fee->fresh()->status);
        $this->assertFalse($fee->fresh()->isFullyPaid());

        $fee->update(['paid_amount' => '50000.00']);
        $fee->recalculateBalance();

        $this->assertSame('0.00', $fee->fresh()->balance);
        $this->assertSame('paid', $fee->fresh()->status);
        $this->assertTrue($fee->fresh()->isFullyPaid());
    }

    public function test_unpaid_and_overdue_scopes_and_method_match_fee_state(): void
    {
        $context = $this->academicContext();
        $structure = $this->createFeeStructure($context);
        $student = $this->createStudent('S-012');
        $overdue = $this->createStudentFee($student, $structure, $structure->items->first(), [
            'due_date' => today()->subDay()->toDateString(),
        ]);
        $paid = $this->createStudentFee(
            $this->createStudent('S-013'),
            $structure,
            $structure->items->last(),
            [
                'status' => 'paid',
                'amount' => '25000.00',
                'paid_amount' => '25000.00',
                'balance' => '0.00',
                'due_date' => today()->subDay()->toDateString(),
            ]
        );

        $this->assertTrue($overdue->isOverdue());
        $this->assertFalse($paid->isOverdue());
        $this->assertSame([$overdue->id], StudentFee::query()->unpaid()->pluck('id')->all());
        $this->assertSame([$overdue->id], StudentFee::query()->overdue()->pluck('id')->all());
    }

    public function test_permissions_migration_grants_only_the_requested_roles_and_actions(): void
    {
        $roles = collect([
            'Super Administrator',
            'School Administrator',
            'Finance Officer',
            'Principal',
            'Receptionist',
            'Teacher',
        ])->mapWithKeys(fn (string $name): array => [
            $name => Role::create(['name' => $name]),
        ]);

        $migration = require database_path(
            'migrations/2026_10_02_130100_add_student_fees_permissions.php'
        );
        $migration->up();

        foreach (['Super Administrator', 'School Administrator'] as $roleName) {
            $this->assertSame(
                3,
                $roles[$roleName]->fresh()->permissions()
                    ->where('name', 'like', 'student_fees.%')
                    ->count()
            );
        }

        $this->assertSame(
            ['student_fees.generate', 'student_fees.view'],
            $roles['Finance Officer']->fresh()->permissions()
                ->where('name', 'like', 'student_fees.%')
                ->orderBy('name')
                ->pluck('name')
                ->all()
        );
        $this->assertSame(
            ['student_fees.view'],
            $roles['Principal']->fresh()->permissions()
                ->where('name', 'like', 'student_fees.%')
                ->pluck('name')
                ->all()
        );
        $this->assertSame(
            ['student_fees.view'],
            $roles['Receptionist']->fresh()->permissions()
                ->where('name', 'like', 'student_fees.%')
                ->pluck('name')
                ->all()
        );
        $this->assertSame(
            0,
            $roles['Teacher']->permissions()
                ->where('name', 'like', 'student_fees.%')
                ->count()
        );
    }

    private function userWithPermissions(array $permissionNames): User
    {
        return $this->userWithRolePermissions('Test Student Fee Role', $permissionNames);
    }

    private function userWithRolePermissions(string $roleName, array $permissionNames): User
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => $roleName]);

        foreach ($permissionNames as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $role->permissions()->attach($permission->id);
        }

        $user->roles()->attach($role->id);

        return $user;
    }

    private function academicContext(string $yearName = '2026', string $className = 'Form 1'): array
    {
        $year = $this->createAcademicYear($yearName);
        $term = $this->createTerm($year);
        $class = ClassRoom::create([
            'name' => $className,
            'description' => null,
            'status' => 'active',
        ]);

        return compact('year', 'term', 'class');
    }

    private function createAcademicYear(string $name): AcademicYear
    {
        return AcademicYear::create([
            'name' => $name,
            'start_date' => $name.'-01-01',
            'end_date' => $name.'-12-31',
            'is_current' => false,
            'status' => 'active',
        ]);
    }

    private function createTerm(AcademicYear $year, string $name = 'Term 1'): Term
    {
        return Term::create([
            'academic_year_id' => $year->id,
            'name' => $name,
            'start_date' => $year->start_date->toDateString(),
            'end_date' => $year->end_date->toDateString(),
            'status' => 'active',
        ]);
    }

    private function createFeeStructure(array $context, array $overrides = []): FeeStructure
    {
        $feeStructure = FeeStructure::create(array_replace([
            'academic_year_id' => $context['year']->id,
            'term_id' => $context['term']->id,
            'class_id' => $context['class']->id,
            'name' => 'Form 1 Term 1 Fees 2026',
            'description' => 'Standard term fees.',
            'total_amount' => 95000,
            'status' => 'active',
            'created_by' => User::factory()->create()->id,
        ], $overrides));

        $feeStructure->items()->createMany([
            [
                'name' => 'Tuition',
                'amount' => '50000.00',
                'is_mandatory' => true,
                'description' => 'Tuition fees.',
            ],
            [
                'name' => 'Uniform',
                'amount' => '25000.00',
                'is_mandatory' => false,
                'description' => 'Uniform fees.',
            ],
            [
                'name' => 'Library',
                'amount' => '20000.00',
                'is_mandatory' => true,
                'description' => 'Library fees.',
            ],
        ]);

        return $feeStructure->load('items');
    }

    private function createStudent(string $admissionNumber): Student
    {
        return Student::create([
            'admission_number' => $admissionNumber,
            'first_name' => 'Test',
            'last_name' => 'Student '.$admissionNumber,
            'date_of_birth' => '2010-01-01',
            'gender' => 'Other',
            'status' => 'active',
        ]);
    }

    private function createEnrollment(Student $student, array $context): StudentEnrollment
    {
        return StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $context['year']->id,
            'class_id' => $context['class']->id,
            'status' => 'active',
            'enrollment_date' => $context['year']->start_date,
        ]);
    }

    private function createStudentFee(
        Student $student,
        FeeStructure $structure,
        $item,
        array $overrides = []
    ): StudentFee {
        return StudentFee::create(array_replace([
            'student_id' => $student->id,
            'fee_structure_id' => $structure->id,
            'fee_structure_item_id' => $item->id,
            'amount' => $item->amount,
            'paid_amount' => '0.00',
            'balance' => $item->amount,
            'status' => 'unpaid',
            'due_date' => today()->addMonth()->toDateString(),
            'created_by' => User::factory()->create()->id,
        ], $overrides));
    }

    private function generationPayload(array $context, array $overrides = []): array
    {
        return array_replace([
            'academic_year_id' => $context['year']->id,
            'term_id' => $context['term']->id,
            'class_id' => $context['class']->id,
            'due_date' => today()->addMonth()->toDateString(),
        ], $overrides);
    }
}
