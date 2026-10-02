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
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $payment = $this->createPayment();

        $this->get(route('receipts.show', $payment))->assertRedirect(route('login'));
        $this->get(route('receipts.print', $payment))->assertRedirect(route('login'));
    }

    public function test_user_without_receipt_view_permission_is_forbidden(): void
    {
        $user = $this->userWithPermissions([]);
        $payment = $this->createPayment();

        $this->actingAs($user)
            ->get(route('receipts.show', $payment))
            ->assertForbidden();
    }

    public function test_user_without_receipt_print_permission_is_forbidden_from_print_and_pdf(): void
    {
        $user = $this->userWithPermissions(['receipts.view']);
        $payment = $this->createPayment();

        $this->actingAs($user)
            ->get(route('receipts.print', $payment))
            ->assertForbidden();
        $this->get(route('receipts.pdf', $payment))
            ->assertForbidden();
    }

    public function test_finance_officer_can_view_and_print_receipts(): void
    {
        $user = $this->userWithRolePermissions('Finance Officer', [
            'receipts.view',
            'receipts.print',
        ]);
        $payment = $this->createPayment();

        $this->actingAs($user)
            ->get(route('receipts.show', $payment))
            ->assertOk()
            ->assertSee($payment->receipt_number);

        $this->get(route('receipts.print', $payment))
            ->assertOk()
            ->assertSee($payment->receipt_number);
    }

    public function test_show_displays_completed_payment_student_class_and_fee_balances(): void
    {
        $user = $this->userWithPermissions(['receipts.view']);
        $payment = $this->createPayment();
        $payment->student->update([
            'first_name' => 'Amina',
            'last_name' => 'Mashauri',
            'admission_number' => 'ADM-2026-0042',
        ]);

        $this->actingAs($user)
            ->get(route('receipts.show', $payment))
            ->assertOk()
            ->assertSee($payment->receipt_number)
            ->assertSee('Amina')
            ->assertSee('Mashauri')
            ->assertSee('ADM-2026-0042')
            ->assertSee($payment->studentFee->feeStructure->classRoom->name)
            ->assertSee('Tuition')
            ->assertSee('TZS 100,000.00')
            ->assertSee('TZS 25,000.00')
            ->assertSee('TZS 75,000.00')
            ->assertSee('Cash')
            ->assertSee('COMPLETED')
            ->assertSee('Thank you.');
    }

    public function test_reversed_payment_remains_viewable_with_reversal_banner_date_and_reason(): void
    {
        $user = $this->userWithPermissions(['receipts.view']);
        $payment = $this->createPayment([
            'status' => 'reversed',
            'reversed_at' => '2026-10-02 12:30:00',
            'reversed_by' => User::factory()->create()->id,
            'reversal_reason' => 'Recorded against the wrong student.',
        ]);

        $this->actingAs($user)
            ->get(route('receipts.show', $payment))
            ->assertOk()
            ->assertSee($payment->receipt_number)
            ->assertSee('REVERSED')
            ->assertSee('not a valid receipt')
            ->assertSee('2026-10-02 12:30:00')
            ->assertSee('Recorded against the wrong student.');
    }

    public function test_print_route_renders_standalone_printable_receipt(): void
    {
        $user = $this->userWithPermissions(['receipts.print']);
        $payment = $this->createPayment();

        $this->actingAs($user)
            ->get(route('receipts.print', $payment))
            ->assertOk()
            ->assertSee('<!DOCTYPE html>', false)
            ->assertSee('window.print()')
            ->assertSee($payment->receipt_number)
            ->assertDontSee('Dashboard')
            ->assertDontSee('Please correct these errors:');
    }

    public function test_pdf_route_returns_application_pdf_when_dompdf_is_installed(): void
    {
        if (! class_exists(Pdf::class)) {
            $this->markTestSkipped('Install barryvdh/laravel-dompdf to test PDF output.');
        }

        $user = $this->userWithPermissions(['receipts.print']);
        $payment = $this->createPayment();

        $this->actingAs($user)
            ->get(route('receipts.pdf', $payment))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_pdf_route_explains_missing_dompdf_dependency(): void
    {
        if (class_exists(Pdf::class)) {
            $this->markTestSkipped('DomPDF is installed, so the missing-package fallback is not applicable.');
        }

        $user = $this->userWithPermissions(['receipts.print']);
        $payment = $this->createPayment();

        $response = $this->actingAs($user)
            ->get(route('receipts.pdf', $payment))
            ->assertServiceUnavailable();

        $this->assertStringContainsString(
            'composer require barryvdh/laravel-dompdf',
            $response->getContent()
        );
    }

    public function test_receipt_permissions_migration_grants_only_the_requested_roles(): void
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
            'migrations/2026_10_02_134000_add_receipts_permissions.php'
        );
        $migration->up();

        foreach (['Super Administrator', 'School Administrator', 'Finance Officer'] as $roleName) {
            $this->assertSame(
                ['receipts.print', 'receipts.view'],
                $roles[$roleName]->fresh()->permissions()
                    ->where('name', 'like', 'receipts.%')
                    ->orderBy('name')
                    ->pluck('name')
                    ->all()
            );
        }

        $this->assertSame(
            ['receipts.view'],
            $roles['Principal']->fresh()->permissions()
                ->where('name', 'like', 'receipts.%')
                ->pluck('name')
                ->all()
        );
        $this->assertSame(
            ['receipts.print', 'receipts.view'],
            $roles['Receptionist']->fresh()->permissions()
                ->where('name', 'like', 'receipts.%')
                ->orderBy('name')
                ->pluck('name')
                ->all()
        );
        $this->assertSame(
            0,
            $roles['Teacher']->permissions()
                ->where('name', 'like', 'receipts.%')
                ->count()
        );
    }

    public function test_unsupported_receipt_creation_and_edit_permissions_are_removed(): void
    {
        $role = Role::create(['name' => 'Legacy Receipt Role']);
        $permissionIds = collect([
            'receipts.create',
            'receipts.generate',
            'receipts.update',
            'receipts.delete',
        ])->map(function (string $name) use ($role): int {
            $permission = Permission::create(['name' => $name]);
            $role->permissions()->attach($permission->id);

            return $permission->id;
        });

        $migration = require database_path(
            'migrations/2026_10_02_134100_remove_unsupported_receipt_permissions.php'
        );
        $migration->up();

        $this->assertSame(
            0,
            $role->fresh()->permissions()
                ->where('name', 'like', 'receipts.%')
                ->count()
        );
        foreach ($permissionIds as $permissionId) {
            $this->assertDatabaseMissing('permissions', ['id' => $permissionId]);
        }
    }

    private function userWithPermissions(array $permissionNames): User
    {
        return $this->userWithRolePermissions('Test Receipt Role', $permissionNames);
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

    private function createPayment(array $overrides = []): Payment
    {
        $student = Student::create([
            'admission_number' => 'RECEIPT-'.(Student::query()->count() + 1),
            'first_name' => 'Receipt',
            'last_name' => 'Student',
            'date_of_birth' => '2010-01-01',
            'gender' => 'Other',
            'status' => 'active',
        ]);
        $yearNumber = AcademicYear::query()->count() + 1;
        $year = AcademicYear::create([
            'name' => 'Receipt Year '.$yearNumber,
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
            'name' => 'Receipt Class '.(ClassRoom::query()->count() + 1),
            'description' => null,
            'status' => 'active',
        ]);
        $structure = FeeStructure::create([
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'class_id' => $class->id,
            'name' => 'Receipt Fee Structure '.$yearNumber,
            'description' => null,
            'total_amount' => '100000.00',
            'status' => 'active',
            'created_by' => User::factory()->create()->id,
        ]);
        $item = FeeStructureItem::create([
            'fee_structure_id' => $structure->id,
            'name' => 'Tuition',
            'amount' => '100000.00',
            'is_mandatory' => true,
            'description' => null,
        ]);
        $studentFee = StudentFee::create([
            'student_id' => $student->id,
            'fee_structure_id' => $structure->id,
            'fee_structure_item_id' => $item->id,
            'amount' => '100000.00',
            'paid_amount' => '25000.00',
            'balance' => '75000.00',
            'status' => 'partially_paid',
            'due_date' => '2026-12-01',
            'created_by' => User::factory()->create()->id,
        ]);

        return Payment::create(array_replace([
            'student_fee_id' => $studentFee->id,
            'student_id' => $student->id,
            'amount' => '25000.00',
            'payment_method' => 'cash',
            'reference_number' => 'CASH-RECEIPT-001',
            'receipt_number' => 'RCP-202610-'.str_pad((string) (Payment::query()->count() + 1), 5, '0', STR_PAD_LEFT),
            'paid_date' => '2026-10-02',
            'recorded_by' => User::factory()->create()->id,
            'status' => 'completed',
            'reversed_at' => null,
            'reversed_by' => null,
            'reversal_reason' => null,
            'remarks' => 'Term fees payment.',
        ], $overrides));
    }
}
