<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\FeeStructure;
use App\Models\FeeStructureItem;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentFee;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $this->get(route('payments.index'))->assertRedirect(route('login'));
        $this->get(route('payments.create'))->assertRedirect(route('login'));
    }

    public function test_user_without_view_permission_receives_forbidden_response(): void
    {
        $user = $this->userWithPermissions([]);

        $this->actingAs($user)
            ->get(route('payments.index'))
            ->assertForbidden();
    }

    public function test_user_without_create_permission_receives_forbidden_response(): void
    {
        $user = $this->userWithPermissions(['payments.view']);
        $studentFee = $this->createStudentFee();

        $this->actingAs($user)
            ->get(route('payments.create'))
            ->assertForbidden();
        $this->post(route('payments.store'), $this->validPayload($studentFee))
            ->assertForbidden();
    }

    public function test_user_without_reverse_permission_receives_forbidden_response(): void
    {
        $user = $this->userWithPermissions(['payments.view']);
        $payment = $this->createPayment();

        $this->actingAs($user)
            ->post(route('payments.reverse', $payment), [
                'reversal_reason' => 'Duplicate payment recorded.',
            ])
            ->assertForbidden();

        $this->assertSame('completed', $payment->fresh()->status);
    }

    public function test_finance_officer_can_record_payment_but_cannot_reverse_it(): void
    {
        $user = $this->userWithRolePermissions('Finance Officer', [
            'payments.view',
            'payments.create',
        ]);
        $studentFee = $this->createStudentFee();

        $this->actingAs($user)
            ->post(route('payments.store'), $this->validPayload($studentFee, [
                'amount' => '10.00',
            ]))
            ->assertRedirect();

        $payment = Payment::query()->sole();
        $this->post(route('payments.reverse', $payment), [
            'reversal_reason' => 'Reverse this recorded payment.',
        ])->assertForbidden();

        $this->assertSame('completed', $payment->fresh()->status);
        $this->assertSame('10.00', $studentFee->fresh()->paid_amount);
    }

    public function test_valid_payment_creates_record_and_updates_student_fee_balance(): void
    {
        $user = $this->userWithPermissions(['payments.create']);
        $studentFee = $this->createStudentFee();

        $this->actingAs($user)
            ->post(route('payments.store'), $this->validPayload($studentFee, [
                'amount' => '12500.00',
                'payment_method' => 'mobile_money',
                'reference_number' => 'MOBILE-001',
                'remarks' => 'Paid at the school office.',
                'receipt_number' => 'RCP-ATTACKER-SET',
                'student_id' => 999999,
                'recorded_by' => 999999,
                'status' => 'reversed',
            ]))
            ->assertRedirect();

        $payment = Payment::query()->sole();
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'student_fee_id' => $studentFee->id,
            'student_id' => $studentFee->student_id,
            'amount' => '12500.00',
            'payment_method' => 'mobile_money',
            'reference_number' => 'MOBILE-001',
            'receipt_number' => $payment->receipt_number,
            'status' => 'completed',
            'recorded_by' => $user->id,
        ]);
        $this->assertMatchesRegularExpression('/\ARCP-\d{6}-\d{5}\z/', $payment->receipt_number);
        $this->assertSame('12500.00', $studentFee->fresh()->paid_amount);
        $this->assertSame('37500.00', $studentFee->fresh()->balance);
        $this->assertSame('partially_paid', $studentFee->fresh()->status);
    }

    public function test_payment_amount_above_remaining_balance_is_rejected_without_writing(): void
    {
        $user = $this->userWithPermissions(['payments.create']);
        $studentFee = $this->createStudentFee();

        $this->actingAs($user)
            ->from(route('payments.create'))
            ->post(route('payments.store'), $this->validPayload($studentFee, [
                'amount' => '50000.01',
            ]))
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('0.00', $studentFee->fresh()->paid_amount);
    }

    public function test_zero_payment_amount_is_rejected_without_writing(): void
    {
        $user = $this->userWithPermissions(['payments.create']);
        $studentFee = $this->createStudentFee();

        $this->actingAs($user)
            ->from(route('payments.create'))
            ->post(route('payments.store'), $this->validPayload($studentFee, [
                'amount' => '0.00',
            ]))
            ->assertSessionHasErrors('amount');

        $this->from(route('payments.create'))
            ->post(route('payments.store'), $this->validPayload($studentFee, [
                'amount' => '-0.01',
            ]))
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_on_already_paid_student_fee_is_rejected(): void
    {
        $user = $this->userWithPermissions(['payments.create']);
        $studentFee = $this->createStudentFee([
            'amount' => '50000.00',
            'paid_amount' => '50000.00',
            'balance' => '0.00',
            'status' => 'paid',
        ]);

        $this->actingAs($user)
            ->from(route('payments.create'))
            ->post(route('payments.store'), $this->validPayload($studentFee, [
                'amount' => '1.00',
            ]))
            ->assertSessionHasErrors('student_fee_id');

        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('paid', $studentFee->fresh()->status);
    }

    public function test_payment_on_waived_student_fee_is_rejected(): void
    {
        $user = $this->userWithPermissions(['payments.create']);
        $studentFee = $this->createStudentFee([
            'status' => 'waived',
        ]);

        $this->actingAs($user)
            ->from(route('payments.create'))
            ->post(route('payments.store'), $this->validPayload($studentFee, [
                'amount' => '1.00',
            ]))
            ->assertSessionHasErrors('student_fee_id');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_for_inactive_student_is_rejected(): void
    {
        $user = $this->userWithPermissions(['payments.create']);
        $studentFee = $this->createStudentFee();
        $studentFee->student->update(['status' => 'inactive']);

        $this->actingAs($user)
            ->from(route('payments.create'))
            ->post(route('payments.store'), $this->validPayload($studentFee))
            ->assertSessionHasErrors('student_fee_id');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_future_paid_date_is_rejected_without_writing(): void
    {
        $user = $this->userWithPermissions(['payments.create']);
        $studentFee = $this->createStudentFee();

        $this->actingAs($user)
            ->from(route('payments.create'))
            ->post(route('payments.store'), $this->validPayload($studentFee, [
                'paid_date' => today()->addDay()->toDateString(),
            ]))
            ->assertSessionHasErrors('paid_date');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_full_payment_marks_student_fee_as_paid(): void
    {
        $user = $this->userWithPermissions(['payments.create']);
        $studentFee = $this->createStudentFee();

        $this->actingAs($user)
            ->post(route('payments.store'), $this->validPayload($studentFee, [
                'amount' => '50000.00',
            ]))
            ->assertRedirect();

        $this->assertSame('50000.00', $studentFee->fresh()->paid_amount);
        $this->assertSame('0.00', $studentFee->fresh()->balance);
        $this->assertSame('paid', $studentFee->fresh()->status);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_receipt_numbers_are_monthly_sequential_and_unique(): void
    {
        $this->travelTo('2026-10-02 10:00:00');

        $receipts = [
            Payment::generateReceiptNumber(),
            Payment::generateReceiptNumber(),
            Payment::generateReceiptNumber(),
        ];

        $this->assertCount(3, array_unique($receipts));
        $this->assertSame([
            'RCP-202610-00001',
            'RCP-202610-00002',
            'RCP-202610-00003',
        ], $receipts);
    }

    public function test_reversing_completed_payment_updates_fee_and_preserves_audit_record(): void
    {
        $user = $this->userWithPermissions(['payments.reverse']);
        $studentFee = $this->createStudentFee([
            'paid_amount' => '20000.00',
            'balance' => '30000.00',
            'status' => 'partially_paid',
        ]);
        $payment = $this->createPayment($studentFee, [
            'amount' => '20000.00',
        ]);
        $reason = 'Entered against the wrong student.';

        $this->actingAs($user)
            ->post(route('payments.reverse', $payment), ['reversal_reason' => $reason])
            ->assertRedirect(route('payments.show', $payment))
            ->assertSessionHas('success', 'Payment reversed successfully.');

        $payment->refresh();
        $this->assertSame('reversed', $payment->status);
        $this->assertNotNull($payment->reversed_at);
        $this->assertSame($user->id, $payment->reversed_by);
        $this->assertSame($reason, $payment->reversal_reason);
        $this->assertSame(1, Payment::query()->count());
        $this->assertSame('0.00', $studentFee->fresh()->paid_amount);
        $this->assertSame('50000.00', $studentFee->fresh()->balance);
        $this->assertSame('unpaid', $studentFee->fresh()->status);
    }

    public function test_payment_lifecycle_moves_from_unpaid_to_partial_to_paid_and_back_to_partial_on_reversal(): void
    {
        $financeOfficer = $this->userWithPermissions(['payments.create']);
        $administrator = $this->userWithRolePermissions('Payment Reverser', ['payments.reverse']);
        $studentFee = $this->createStudentFee();

        $this->actingAs($financeOfficer)
            ->post(route('payments.store'), $this->validPayload($studentFee, [
                'amount' => '20000.00',
            ]))
            ->assertRedirect();

        $this->assertSame('partially_paid', $studentFee->fresh()->status);
        $this->assertSame('30000.00', $studentFee->fresh()->balance);

        $this->post(route('payments.store'), $this->validPayload($studentFee, [
            'amount' => '30000.00',
        ]))->assertRedirect();

        $secondPayment = Payment::query()->latest('id')->firstOrFail();
        $this->assertSame('paid', $studentFee->fresh()->status);
        $this->assertSame('0.00', $studentFee->fresh()->balance);

        $this->actingAs($administrator)
            ->post(route('payments.reverse', $secondPayment), [
                'reversal_reason' => 'Correction to the amount collected.',
            ])
            ->assertRedirect(route('payments.show', $secondPayment));

        $this->assertSame('reversed', $secondPayment->fresh()->status);
        $this->assertSame('partially_paid', $studentFee->fresh()->status);
        $this->assertSame('20000.00', $studentFee->fresh()->paid_amount);
        $this->assertSame('30000.00', $studentFee->fresh()->balance);
        $this->assertSame(2, Payment::query()->count());
    }

    public function test_reversed_payment_cannot_be_reversed_again(): void
    {
        $user = $this->userWithPermissions(['payments.reverse']);
        $studentFee = $this->createStudentFee();
        $payment = $this->createPayment($studentFee, [
            'status' => 'reversed',
            'reversed_at' => now(),
            'reversed_by' => User::factory()->create()->id,
            'reversal_reason' => 'Previously reversed correctly.',
        ]);

        $this->actingAs($user)
            ->from(route('payments.show', $payment))
            ->post(route('payments.reverse', $payment), [
                'reversal_reason' => 'Attempt a second reversal.',
            ])
            ->assertSessionHasErrors('payment');

        $this->assertSame('reversed', $payment->fresh()->status);
        $this->assertSame('Previously reversed correctly.', $payment->fresh()->reversal_reason);
    }

    public function test_reversal_requires_a_reason(): void
    {
        $user = $this->userWithPermissions(['payments.reverse']);
        $payment = $this->createPayment();

        $this->actingAs($user)
            ->from(route('payments.show', $payment))
            ->post(route('payments.reverse', $payment))
            ->assertSessionHasErrors('reversal_reason');

        $this->assertSame('completed', $payment->fresh()->status);
    }

    public function test_reversal_reason_must_contain_at_least_ten_characters(): void
    {
        $user = $this->userWithPermissions(['payments.reverse']);
        $payment = $this->createPayment();

        $this->actingAs($user)
            ->from(route('payments.show', $payment))
            ->post(route('payments.reverse', $payment), [
                'reversal_reason' => 'Too short',
            ])
            ->assertSessionHasErrors('reversal_reason');

        $this->assertSame('completed', $payment->fresh()->status);
    }

    public function test_view_permission_lists_filters_and_searches_payments(): void
    {
        $user = $this->userWithPermissions(['payments.view']);
        $studentFee = $this->createStudentFee();
        $target = $this->createPayment($studentFee, [
            'receipt_number' => 'RCP-202610-00421',
            'reference_number' => 'BANK-XYZ-921',
            'payment_method' => 'bank_transfer',
        ]);
        $otherPayment = $this->createPayment($this->createStudentFee(), [
            'payment_method' => 'cash',
        ]);

        $this->actingAs($user)
            ->get(route('payments.index', [
                'student_id' => $studentFee->student_id,
                'student_fee_id' => $studentFee->id,
                'status' => 'completed',
                'payment_method' => 'bank_transfer',
                'date_from' => today()->toDateString(),
                'date_to' => today()->toDateString(),
                'search' => 'BANK-XYZ',
            ]))
            ->assertOk()
            ->assertSee($target->receipt_number)
            ->assertSee($studentFee->student->admission_number)
            ->assertDontSee($otherPayment->receipt_number);
    }

    public function test_view_permission_shows_payment_and_reversal_form_for_completed_payment(): void
    {
        $user = $this->userWithPermissions(['payments.view', 'payments.reverse']);
        $payment = $this->createPayment();

        $this->actingAs($user)
            ->get(route('payments.show', $payment))
            ->assertOk()
            ->assertSee($payment->receipt_number)
            ->assertSee($payment->student->admission_number)
            ->assertSee($payment->studentFee->feeStructureItem->name)
            ->assertSee('Reverse payment');
    }

    public function test_payment_details_escape_untrusted_reference_remarks_and_reversal_reason(): void
    {
        $user = $this->userWithPermissions(['payments.view']);
        $payload = '<script>alert("bad")</script>';
        $payment = $this->createPayment(null, [
            'reference_number' => $payload,
            'remarks' => $payload,
            'status' => 'reversed',
            'reversed_at' => now(),
            'reversal_reason' => $payload,
        ]);

        $this->actingAs($user)
            ->get(route('payments.show', $payment))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(&quot;bad&quot;)&lt;/script&gt;', false)
            ->assertDontSee($payload, false);
    }

    public function test_payment_resource_has_no_edit_update_or_delete_routes(): void
    {
        $this->assertFalse(Route::has('payments.edit'));
        $this->assertFalse(Route::has('payments.update'));
        $this->assertFalse(Route::has('payments.destroy'));
        $this->assertCount(5, collect(Route::getRoutes()->getRoutesByName())
            ->filter(fn ($route) => str_starts_with($route->getName() ?? '', 'payments.')));
    }

    public function test_payment_scopes_and_status_methods_match_payment_state(): void
    {
        $completed = $this->createPayment();
        $reversed = $this->createPayment($this->createStudentFee(), [
            'status' => 'reversed',
            'reversed_at' => now(),
            'reversal_reason' => 'Payment was entered incorrectly.',
        ]);

        $this->assertTrue($completed->isCompleted());
        $this->assertFalse($completed->isReversed());
        $this->assertTrue($reversed->isReversed());
        $this->assertFalse($reversed->isCompleted());
        $this->assertSame([$completed->id], Payment::query()->completed()->pluck('id')->all());
        $this->assertSame([$reversed->id], Payment::query()->reversed()->pluck('id')->all());
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
            'migrations/2026_10_02_133100_add_payments_permissions.php'
        );
        $migration->up();

        foreach (['Super Administrator', 'School Administrator'] as $roleName) {
            $this->assertSame(
                ['payments.create', 'payments.reverse', 'payments.view'],
                $roles[$roleName]->fresh()->permissions()
                    ->where('name', 'like', 'payments.%')
                    ->orderBy('name')
                    ->pluck('name')
                    ->all()
            );
        }

        $this->assertSame(
            ['payments.create', 'payments.view'],
            $roles['Finance Officer']->fresh()->permissions()
                ->where('name', 'like', 'payments.%')
                ->orderBy('name')
                ->pluck('name')
                ->all()
        );
        foreach (['Principal', 'Receptionist'] as $roleName) {
            $this->assertSame(
                ['payments.view'],
                $roles[$roleName]->fresh()->permissions()
                    ->where('name', 'like', 'payments.%')
                    ->pluck('name')
                    ->all()
            );
        }
        $this->assertSame(
            0,
            $roles['Teacher']->permissions()
                ->where('name', 'like', 'payments.%')
                ->count()
        );
    }

    public function test_unsupported_payment_edit_permissions_are_removed(): void
    {
        $role = Role::create(['name' => 'Legacy Finance Role']);
        $permissionIds = collect(['payments.update', 'payments.delete'])
            ->map(function (string $name) use ($role): int {
                $permission = Permission::create(['name' => $name]);
                $role->permissions()->attach($permission->id);

                return $permission->id;
            });

        $migration = require database_path(
            'migrations/2026_10_02_133200_remove_unsupported_payment_permissions.php'
        );
        $migration->up();

        $this->assertSame(
            0,
            $role->fresh()->permissions()
                ->whereIn('name', ['payments.update', 'payments.delete'])
                ->count()
        );
        $this->assertDatabaseMissing('permissions', ['id' => $permissionIds->first()]);
        $this->assertDatabaseMissing('permissions', ['id' => $permissionIds->last()]);
    }

    private function userWithPermissions(array $permissionNames): User
    {
        return $this->userWithRolePermissions('Test Payment Role', $permissionNames);
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

    private function createStudentFee(array $overrides = []): StudentFee
    {
        $student = $this->createStudent();
        $yearName = 'Payment Year '.(AcademicYear::query()->count() + 1);
        $year = AcademicYear::create([
            'name' => $yearName,
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
            'name' => 'Payment Class '.(ClassRoom::query()->count() + 1),
            'description' => null,
            'status' => 'active',
        ]);
        $structure = FeeStructure::create([
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'class_id' => $class->id,
            'name' => 'Form 1 Term 1 Fees 2026',
            'description' => null,
            'total_amount' => '50000.00',
            'status' => 'active',
            'created_by' => User::factory()->create()->id,
        ]);
        $item = FeeStructureItem::create([
            'fee_structure_id' => $structure->id,
            'name' => 'Tuition',
            'amount' => '50000.00',
            'is_mandatory' => true,
            'description' => null,
        ]);

        return StudentFee::create(array_replace([
            'student_id' => $student->id,
            'fee_structure_id' => $structure->id,
            'fee_structure_item_id' => $item->id,
            'amount' => '50000.00',
            'paid_amount' => '0.00',
            'balance' => '50000.00',
            'status' => 'unpaid',
            'due_date' => today()->addMonth()->toDateString(),
            'created_by' => User::factory()->create()->id,
        ], $overrides));
    }

    private function createStudent(): Student
    {
        $studentNumber = Student::query()->count() + 1;

        return Student::create([
            'admission_number' => 'PAY-ST-'.$studentNumber,
            'first_name' => 'Payment',
            'last_name' => 'Student '.$studentNumber,
            'date_of_birth' => '2010-01-01',
            'gender' => 'Other',
            'status' => 'active',
        ]);
    }

    private function createPayment(?StudentFee $studentFee = null, array $overrides = []): Payment
    {
        $studentFee ??= $this->createStudentFee();
        $paidDate = today()->toDateString();
        $receiptNumber = Payment::generateReceiptNumber();

        return Payment::create(array_replace([
            'student_fee_id' => $studentFee->id,
            'student_id' => $studentFee->student_id,
            'amount' => '10000.00',
            'payment_method' => 'cash',
            'reference_number' => null,
            'receipt_number' => $receiptNumber,
            'paid_date' => $paidDate,
            'recorded_by' => User::factory()->create()->id,
            'status' => 'completed',
            'reversed_at' => null,
            'reversed_by' => null,
            'reversal_reason' => null,
            'remarks' => null,
        ], $overrides));
    }

    private function validPayload(StudentFee $studentFee, array $overrides = []): array
    {
        return array_replace([
            'student_fee_id' => $studentFee->id,
            'amount' => '10000.00',
            'payment_method' => 'cash',
            'reference_number' => null,
            'paid_date' => today()->toDateString(),
            'remarks' => null,
        ], $overrides);
    }
}
