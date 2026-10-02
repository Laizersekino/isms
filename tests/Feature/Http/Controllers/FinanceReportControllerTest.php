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
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class FinanceReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $this->get(route('reports.finance.collection-summary'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_finance_report_view_permission_receives_403(): void
    {
        $user = $this->userWithPermissions([]);

        $this->actingAs($user)
            ->get(route('reports.finance.collection-summary'))
            ->assertForbidden();
    }

    public function test_receptionist_without_finance_report_permission_receives_403(): void
    {
        $user = $this->userWithRolePermissions('Receptionist', []);

        $this->actingAs($user)
            ->get(route('reports.finance.collection-summary'))
            ->assertForbidden();
    }

    public function test_user_with_view_permission_can_render_finance_report(): void
    {
        $user = $this->userWithPermissions(['reports.finance.view']);

        $this->actingAs($user)
            ->get(route('reports.finance.collection-summary'))
            ->assertOk()
            ->assertSee('Fee Collection Summary')
            ->assertSee('Total expected');
    }

    public function test_user_without_export_permission_receives_403_for_csv(): void
    {
        $user = $this->userWithPermissions(['reports.finance.view']);

        $this->actingAs($user)
            ->get(route('reports.finance.collection-summary', ['format' => 'csv']))
            ->assertForbidden();
    }

    public function test_collection_summary_aggregates_nine_fees_and_completed_payments(): void
    {
        $user = $this->userWithRolePermissions('Finance Officer', [
            'reports.finance.view',
            'reports.finance.export',
        ]);
        $context = $this->createFeeContext();
        $students = [
            $this->createStudent('Collection Student 1'),
            $this->createStudent('Collection Student 2'),
            $this->createStudent('Collection Student 3'),
        ];

        foreach (['Tuition' => '100.00', 'Uniform' => '200.00', 'Library' => '300.00'] as $item => $amount) {
            foreach ($students as $student) {
                $this->createStudentFee($context, $student, $item, $amount);
            }
        }

        $fees = StudentFee::query()->where('student_id', $students[0]->id)->orderBy('id')->get();
        $this->createPayment($fees[0], '100.00');
        $this->createPayment($fees[1], '200.00');
        $this->createPayment($fees[2], '50.00', ['status' => 'reversed']);

        $this->actingAs($user)
            ->get(route('reports.finance.collection-summary'))
            ->assertOk()
            ->assertSee('TZS 1,800.00')
            ->assertSee('TZS 300.00')
            ->assertSee('TZS 1,500.00')
            ->assertSee('16.67%');
    }

    public function test_collection_summary_filters_by_academic_year(): void
    {
        $user = $this->userWithPermissions(['reports.finance.view']);
        $firstContext = $this->createFeeContext('First Report Class', '2025 Report Year');
        $secondContext = $this->createFeeContext('Second Report Class', '2026 Report Year');
        $this->createStudentFee($firstContext, $this->createStudent('First Year Student'), 'Tuition', '100.00');
        $this->createStudentFee($secondContext, $this->createStudent('Second Year Student'), 'Tuition', '250.00');

        $this->actingAs($user)
            ->get(route('reports.finance.collection-summary', [
                'academic_year_id' => $secondContext['year']->id,
            ]))
            ->assertOk()
            ->assertSee('TZS 250.00')
            ->assertDontSee('TZS 100.00');
    }

    public function test_outstanding_report_omits_paid_fees_and_sorts_balances_descending(): void
    {
        $user = $this->userWithPermissions(['reports.finance.view']);
        $context = $this->createFeeContext();
        $largestBalanceStudent = $this->createStudent('Largest Balance');
        $smallerBalanceStudent = $this->createStudent('Smaller Balance');
        $paidStudent = $this->createStudent('Paid Student');
        $largestFee = $this->createStudentFee($context, $largestBalanceStudent, 'Tuition', '300.00');
        $smallerFee = $this->createStudentFee($context, $smallerBalanceStudent, 'Uniform', '200.00');
        $paidFee = $this->createStudentFee($context, $paidStudent, 'Library', '100.00');
        $this->createPayment($smallerFee, '150.00');
        $this->createPayment($paidFee, '100.00');

        $response = $this->actingAs($user)
            ->get(route('reports.finance.outstanding'))
            ->assertOk()
            ->assertSee('Largest Balance')
            ->assertSee('Smaller Balance')
            ->assertDontSee('Paid Student');

        $this->assertLessThan(
            strpos($response->getContent(), 'Smaller Balance'),
            strpos($response->getContent(), 'Largest Balance')
        );
        $this->assertSame('300.00', $largestFee->amount);
    }

    public function test_daily_collection_filters_date_and_groups_completed_payments_by_method(): void
    {
        $user = $this->userWithPermissions(['reports.finance.view']);
        $context = $this->createFeeContext();
        $todayStudent = $this->createStudent('Today Collector');
        $yesterdayStudent = $this->createStudent('Yesterday Collector');
        $todayFee = $this->createStudentFee($context, $todayStudent, 'Tuition', '500.00');
        $yesterdayFee = $this->createStudentFee($context, $yesterdayStudent, 'Uniform', '500.00');
        $todayCash = $this->createPayment($todayFee, '100.00', ['payment_method' => 'cash']);
        $todayMobile = $this->createPayment($todayFee, '50.00', ['payment_method' => 'mobile_money']);
        $this->createPayment($yesterdayFee, '200.00', [
            'paid_date' => today()->subDay()->toDateString(),
        ]);

        $this->actingAs($user)
            ->get(route('reports.finance.daily-collection', ['date' => today()->toDateString()]))
            ->assertOk()
            ->assertSee($todayCash->receipt_number)
            ->assertSee($todayMobile->receipt_number)
            ->assertSee('Today Collector')
            ->assertDontSee('Yesterday Collector')
            ->assertSee('TZS 150.00')
            ->assertSee('Mobile Money');
    }

    public function test_class_collection_reports_expected_collected_and_outstanding_per_class(): void
    {
        $user = $this->userWithPermissions(['reports.finance.view']);
        $firstContext = $this->createFeeContext('Class A');
        $secondContext = $this->createFeeContext('Class B');
        $firstFee = $this->createStudentFee($firstContext, $this->createStudent('Class A Student'), 'Tuition', '400.00');
        $secondFee = $this->createStudentFee($secondContext, $this->createStudent('Class B Student'), 'Tuition', '600.00');
        $this->createPayment($firstFee, '100.00');
        $this->createPayment($secondFee, '600.00');

        $this->actingAs($user)
            ->get(route('reports.finance.class-collection'))
            ->assertOk()
            ->assertSee('Class A')
            ->assertSee('TZS 400.00')
            ->assertSee('TZS 100.00')
            ->assertSee('Class B')
            ->assertSee('TZS 600.00')
            ->assertSee('100.00%');
    }

    public function test_payment_methods_report_aggregates_completed_records_in_date_range(): void
    {
        $user = $this->userWithPermissions(['reports.finance.view']);
        $context = $this->createFeeContext();
        $student = $this->createStudent('Method Report Student');
        $fee = $this->createStudentFee($context, $student, 'Tuition', '900.00');
        $this->createPayment($fee, '100.00', [
            'payment_method' => 'cash',
            'paid_date' => '2026-10-02',
        ]);
        $this->createPayment($fee, '250.00', [
            'payment_method' => 'bank_transfer',
            'paid_date' => '2026-10-01',
        ]);
        $this->createPayment($fee, '300.00', [
            'payment_method' => 'cheque',
            'paid_date' => '2026-09-30',
        ]);

        $this->actingAs($user)
            ->get(route('reports.finance.payment-methods', [
                'date_from' => '2026-10-01',
                'date_to' => '2026-10-02',
            ]))
            ->assertOk()
            ->assertSee('Bank Transfer')
            ->assertSee('Cash')
            ->assertDontSee('Cheque')
            ->assertSee('TZS 250.00')
            ->assertSee('TZS 100.00');
    }

    public function test_student_statement_lists_fees_payments_and_running_balance(): void
    {
        $user = $this->userWithPermissions(['reports.finance.view']);
        $context = $this->createFeeContext();
        $student = $this->createStudent('Statement Student');
        $tuitionFee = $this->createStudentFee($context, $student, 'Tuition', '300.00');
        $uniformFee = $this->createStudentFee($context, $student, 'Uniform', '200.00');
        $completedPayment = $this->createPayment($tuitionFee, '100.00', [
            'paid_date' => '2026-10-01',
        ]);
        $reversedPayment = $this->createPayment($uniformFee, '50.00', [
            'status' => 'reversed',
            'paid_date' => '2026-10-02',
        ]);

        $this->actingAs($user)
            ->get(route('reports.finance.student-statement', $student))
            ->assertOk()
            ->assertSee('Statement Student')
            ->assertSee('Tuition')
            ->assertSee('Uniform')
            ->assertSee($completedPayment->receipt_number)
            ->assertSee($reversedPayment->receipt_number)
            ->assertSee('TZS 500.00')
            ->assertSee('TZS 100.00')
            ->assertSee('TZS 400.00')
            ->assertSee('TZS 400.00');
    }

    public function test_finance_officer_can_export_collection_summary_as_csv(): void
    {
        $user = $this->userWithRolePermissions('Finance Officer', [
            'reports.finance.view',
            'reports.finance.export',
        ]);
        $context = $this->createFeeContext();
        $student = $this->createStudent('=1+1');
        $fee = $this->createStudentFee($context, $student, 'Tuition', '500.00');
        $this->createPayment($fee, '125.00');

        $response = $this->actingAs($user)
            ->get(route('reports.finance.outstanding', ['format' => 'csv']))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Student,"Admission number",Class,Expected,Collected,Balance', $content);
        $this->assertStringContainsString("'=1+1 Student", $content);
        $this->assertStringContainsString('500.00', $content);
        $this->assertStringContainsString('125.00', $content);
    }

    public function test_pdf_export_returns_clear_503_when_dompdf_is_missing(): void
    {
        if (class_exists(Pdf::class)) {
            $this->markTestSkipped('DomPDF is installed, so the missing-package fallback is not applicable.');
        }

        $user = $this->userWithPermissions([
            'reports.finance.view',
            'reports.finance.export',
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.finance.collection-summary', ['format' => 'pdf']))
            ->assertServiceUnavailable();

        $this->assertStringContainsString(
            'composer require barryvdh/laravel-dompdf',
            $response->getContent()
        );
    }

    public function test_excel_export_returns_clear_503_when_laravel_excel_is_missing(): void
    {
        if (class_exists(Excel::class)) {
            $this->markTestSkipped('Laravel Excel is installed, so the missing-package fallback is not applicable.');
        }

        $user = $this->userWithPermissions([
            'reports.finance.view',
            'reports.finance.export',
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.finance.collection-summary', ['format' => 'excel']))
            ->assertServiceUnavailable();

        $this->assertStringContainsString(
            'composer require maatwebsite/excel',
            $response->getContent()
        );
    }

    public function test_finance_report_permission_migration_grants_view_and_export_to_the_requested_roles(): void
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
            'migrations/2026_10_02_135000_add_finance_reports_permissions.php'
        );
        $migration->up();

        foreach (['Super Administrator', 'School Administrator', 'Finance Officer'] as $roleName) {
            $this->assertSame(
                ['reports.finance.export', 'reports.finance.view'],
                $roles[$roleName]->fresh()->permissions()
                    ->whereIn('name', ['reports.finance.view', 'reports.finance.export'])
                    ->orderBy('name')
                    ->pluck('name')
                    ->all()
            );
        }

        foreach (['Principal', 'Receptionist', 'Teacher'] as $roleName) {
            $expectedPermissions = $roleName === 'Principal'
                ? ['reports.finance.view']
                : [];
            $this->assertSame(
                $expectedPermissions,
                $roles[$roleName]->fresh()->permissions()
                    ->whereIn('name', ['reports.finance.view', 'reports.finance.export'])
                    ->orderBy('name')
                    ->pluck('name')
                    ->all()
            );
        }
    }

    private function userWithPermissions(array $permissionNames): User
    {
        return $this->userWithRolePermissions('Test Finance Reports Role', $permissionNames);
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

    /**
     * @return array{year: AcademicYear, term: Term, class: ClassRoom, structure: FeeStructure}
     */
    private function createFeeContext(
        ?string $className = null,
        ?string $yearName = null
    ): array {
        $index = AcademicYear::query()->count() + 1;
        $year = AcademicYear::create([
            'name' => $yearName ?? 'Report Year '.$index,
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
            'name' => $className ?? 'Report Class '.$index,
            'description' => null,
            'status' => 'active',
        ]);
        $structure = FeeStructure::create([
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'class_id' => $class->id,
            'name' => 'Fees '.$year->name,
            'description' => null,
            'total_amount' => '0.00',
            'status' => 'active',
            'created_by' => User::factory()->create()->id,
        ]);

        return compact('year', 'term', 'class', 'structure');
    }

    private function createStudent(string $name): Student
    {
        static $index = 0;
        $index++;

        return Student::create([
            'admission_number' => 'FIN-REPORT-'.$index,
            'first_name' => $name,
            'last_name' => 'Student',
            'date_of_birth' => '2010-01-01',
            'gender' => 'Other',
            'status' => 'active',
        ]);
    }

    private function createStudentFee(
        array $context,
        Student $student,
        string $itemName,
        string $amount
    ): StudentFee {
        $item = FeeStructureItem::query()->firstOrCreate(
            [
                'fee_structure_id' => $context['structure']->id,
                'name' => $itemName,
            ],
            [
                'amount' => $amount,
                'is_mandatory' => true,
                'description' => null,
            ]
        );

        return StudentFee::create([
            'student_id' => $student->id,
            'fee_structure_id' => $context['structure']->id,
            'fee_structure_item_id' => $item->id,
            'amount' => $amount,
            'paid_amount' => '0.00',
            'balance' => $amount,
            'status' => 'unpaid',
            'due_date' => today()->addMonth()->toDateString(),
            'created_by' => User::factory()->create()->id,
        ]);
    }

    private function createPayment(
        StudentFee $studentFee,
        string $amount,
        array $overrides = []
    ): Payment {
        static $index = 0;
        $index++;

        return Payment::create(array_replace([
            'student_fee_id' => $studentFee->id,
            'student_id' => $studentFee->student_id,
            'amount' => $amount,
            'payment_method' => 'cash',
            'reference_number' => null,
            'receipt_number' => sprintf('RCP-REPORT-%05d', $index),
            'paid_date' => today()->toDateString(),
            'recorded_by' => User::factory()->create()->id,
            'status' => 'completed',
            'reversed_at' => null,
            'reversed_by' => null,
            'reversal_reason' => null,
            'remarks' => null,
        ], $overrides));
    }
}
