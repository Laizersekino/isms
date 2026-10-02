<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\FeeStructure;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeeStructureControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $this->get(route('fee-structures.index'))->assertRedirect(route('login'));
        $this->get(route('fee-structures.create'))->assertRedirect(route('login'));
    }

    public function test_user_without_fee_structure_view_permission_receives_forbidden_response(): void
    {
        $user = $this->userWithPermissions([]);

        $this->actingAs($user)
            ->get(route('fee-structures.index'))
            ->assertForbidden();
    }

    public function test_fee_structure_view_permission_lists_and_filters_structures(): void
    {
        $user = $this->userWithPermissions(['fee_structures.view']);
        $first = $this->academicContext();
        $target = $this->createFeeStructure($first, ['name' => 'Form 1 Term 1 Fees']);
        $second = $this->academicContext('2027', 'Term 2', 'Form 2');
        $other = $this->createFeeStructure($second, ['status' => 'active']);

        $this->actingAs($user)
            ->get(route('fee-structures.index', [
                'academic_year_id' => $first['year']->id,
                'term_id' => $first['term']->id,
                'class_id' => $first['class']->id,
                'status' => 'draft',
            ]))
            ->assertOk()
            ->assertSee($target->name)
            ->assertDontSee($other->name)
            ->assertSee('75,000.00');
    }

    public function test_fee_structure_view_permission_shows_details_and_items(): void
    {
        $user = $this->userWithPermissions(['fee_structures.view']);
        $context = $this->academicContext();
        $feeStructure = $this->createFeeStructure($context);

        $this->actingAs($user)
            ->get(route('fee-structures.show', $feeStructure))
            ->assertOk()
            ->assertSee($feeStructure->name)
            ->assertSee($context['year']->name)
            ->assertSee($context['term']->name)
            ->assertSee($context['class']->name)
            ->assertSee('Tuition')
            ->assertSee('Uniform')
            ->assertSee('75,000.00 TZS');
    }

    public function test_fee_structure_create_permission_renders_dynamic_item_form(): void
    {
        $user = $this->userWithPermissions(['fee_structures.create']);
        $context = $this->academicContext();

        $this->actingAs($user)
            ->get(route('fee-structures.create'))
            ->assertOk()
            ->assertSee($context['year']->name)
            ->assertSee($context['term']->name)
            ->assertSee($context['class']->name)
            ->assertSee('id="add-fee-item"', false)
            ->assertSee('fee-item-template');
    }

    public function test_create_stores_structure_and_items_with_total_recalculated(): void
    {
        $user = $this->userWithPermissions(['fee_structures.create']);
        $context = $this->academicContext();

        $this->actingAs($user)
            ->post(route('fee-structures.store'), $this->validPayload($context))
            ->assertRedirect(route('fee-structures.show', FeeStructure::query()->first()))
            ->assertSessionHas('success', 'Fee structure created successfully.');

        $feeStructure = FeeStructure::query()->with('items')->sole();

        $this->assertSame('75000.00', $feeStructure->total_amount);
        $this->assertSame($user->id, $feeStructure->created_by);
        $this->assertCount(2, $feeStructure->items);
        $this->assertDatabaseHas('fee_structure_items', [
            'fee_structure_id' => $feeStructure->id,
            'name' => 'Tuition',
            'amount' => '50000.00',
            'is_mandatory' => true,
        ]);
        $this->assertDatabaseHas('fee_structure_items', [
            'fee_structure_id' => $feeStructure->id,
            'name' => 'Uniform',
            'amount' => '25000.00',
            'is_mandatory' => false,
        ]);
    }

    public function test_store_rejects_missing_items_and_invalid_item_values(): void
    {
        $user = $this->userWithPermissions(['fee_structures.create']);
        $context = $this->academicContext();
        $payload = $this->validPayload($context);

        $this->actingAs($user)
            ->from(route('fee-structures.create'))
            ->post(route('fee-structures.store'), array_diff_key($payload, ['items' => true]))
            ->assertSessionHasErrors('items');

        $this->from(route('fee-structures.create'))
            ->post(route('fee-structures.store'), array_replace($payload, [
                'items' => [[
                    'name' => '',
                    'amount' => -1,
                    'is_mandatory' => 'not-a-boolean',
                    'description' => str_repeat('x', 501),
                ]],
            ]))
            ->assertSessionHasErrors([
                'items.0.name',
                'items.0.amount',
                'items.0.is_mandatory',
                'items.0.description',
            ]);

        $this->assertDatabaseCount('fee_structures', 0);
        $this->assertDatabaseCount('fee_structure_items', 0);
    }

    public function test_store_rejects_term_from_a_different_academic_year(): void
    {
        $user = $this->userWithPermissions(['fee_structures.create']);
        $context = $this->academicContext();
        $otherYear = $this->createAcademicYear('2027');
        $otherTerm = $this->createTerm($otherYear, 'Term 2');

        $this->actingAs($user)
            ->from(route('fee-structures.create'))
            ->post(route('fee-structures.store'), array_replace(
                $this->validPayload($context),
                ['term_id' => $otherTerm->id]
            ))
            ->assertSessionHasErrors('term_id');

        $this->assertDatabaseCount('fee_structures', 0);
    }

    public function test_duplicate_year_term_class_combination_is_rejected(): void
    {
        $user = $this->userWithPermissions(['fee_structures.create']);
        $context = $this->academicContext();
        $this->createFeeStructure($context);

        $this->actingAs($user)
            ->from(route('fee-structures.create'))
            ->post(route('fee-structures.store'), $this->validPayload($context, [
                'name' => 'Duplicate Structure',
            ]))
            ->assertSessionHasErrors('class_id');

        $this->assertDatabaseCount('fee_structures', 1);
    }

    public function test_update_replaces_items_and_recalculates_total(): void
    {
        $user = $this->userWithPermissions(['fee_structures.update']);
        $context = $this->academicContext();
        $feeStructure = $this->createFeeStructure($context);
        $oldItemIds = $feeStructure->items()->pluck('id')->all();
        $replacementItems = [[
            'name' => 'Tuition Revised',
            'amount' => '120000.00',
            'is_mandatory' => 1,
            'description' => 'Updated tuition.',
        ]];

        $this->actingAs($user)
            ->put(route('fee-structures.update', $feeStructure), $this->validPayload($context, [
                'name' => 'Updated Fee Structure',
                'status' => 'active',
                'items' => $replacementItems,
            ]))
            ->assertRedirect(route('fee-structures.show', $feeStructure))
            ->assertSessionHas('success', 'Fee structure updated successfully.');

        $feeStructure->refresh();
        $this->assertSame('Updated Fee Structure', $feeStructure->name);
        $this->assertSame('120000.00', $feeStructure->total_amount);
        $this->assertSame('active', $feeStructure->status);
        $this->assertCount(1, $feeStructure->items);
        $this->assertDatabaseMissing('fee_structure_items', ['id' => $oldItemIds[0]]);
        $this->assertDatabaseMissing('fee_structure_items', ['id' => $oldItemIds[1]]);
        $this->assertDatabaseHas('fee_structure_items', [
            'fee_structure_id' => $feeStructure->id,
            'name' => 'Tuition Revised',
            'amount' => '120000.00',
        ]);
    }

    public function test_update_allows_the_existing_year_term_class_combination(): void
    {
        $user = $this->userWithPermissions(['fee_structures.update']);
        $context = $this->academicContext();
        $feeStructure = $this->createFeeStructure($context);

        $this->actingAs($user)
            ->put(route('fee-structures.update', $feeStructure), $this->validPayload($context, [
                'name' => 'Still Same Combination',
            ]))
            ->assertRedirect(route('fee-structures.show', $feeStructure));

        $this->assertSame('Still Same Combination', $feeStructure->fresh()->name);
    }

    public function test_delete_permission_soft_deletes_fee_structure(): void
    {
        $user = $this->userWithPermissions(['fee_structures.delete']);
        $context = $this->academicContext();
        $feeStructure = $this->createFeeStructure($context);
        $item = $feeStructure->items()->first();

        $this->actingAs($user)
            ->delete(route('fee-structures.destroy', $feeStructure))
            ->assertRedirect(route('fee-structures.index'))
            ->assertSessionHas('success', 'Fee structure archived successfully.');

        $this->assertSoftDeleted('fee_structures', ['id' => $feeStructure->id]);
        $this->assertModelExists($item);
    }

    public function test_finance_officer_cannot_delete_fee_structure(): void
    {
        $user = $this->userWithRolePermissions('Finance Officer', ['fee_structures.view']);
        $feeStructure = $this->createFeeStructure($this->academicContext());

        $this->actingAs($user)
            ->delete(route('fee-structures.destroy', $feeStructure))
            ->assertForbidden();

        $this->assertModelExists($feeStructure);
    }

    public function test_finance_officer_can_create_and_update_fee_structures(): void
    {
        $user = $this->userWithRolePermissions('Finance Officer', [
            'fee_structures.create',
            'fee_structures.update',
        ]);
        $context = $this->academicContext();

        $this->actingAs($user)
            ->post(route('fee-structures.store'), $this->validPayload($context))
            ->assertRedirect();

        $feeStructure = FeeStructure::query()->firstOrFail();
        $this->put(route('fee-structures.update', $feeStructure), $this->validPayload($context, [
            'name' => 'Finance Officer Updated Structure',
        ]))->assertRedirect();

        $this->assertSame('Finance Officer Updated Structure', $feeStructure->fresh()->name);
    }

    public function test_principal_can_view_but_cannot_create_fee_structures(): void
    {
        $user = $this->userWithRolePermissions('Principal', ['fee_structures.view']);
        $context = $this->academicContext();
        $feeStructure = $this->createFeeStructure($context);

        $this->actingAs($user)
            ->get(route('fee-structures.index'))
            ->assertOk()
            ->assertSee($feeStructure->name);

        $this->get(route('fee-structures.show', $feeStructure))->assertOk();
        $this->get(route('fee-structures.create'))->assertForbidden();
        $this->post(route('fee-structures.store'), $this->validPayload($context))->assertForbidden();
    }

    public function test_permissions_migration_grants_only_the_requested_roles_and_actions(): void
    {
        $roles = collect([
            'Super Administrator',
            'School Administrator',
            'Finance Officer',
            'Principal',
            'Teacher',
        ])->mapWithKeys(fn (string $name): array => [
            $name => Role::create(['name' => $name]),
        ]);

        $migration = require database_path(
            'migrations/2026_10_02_123200_add_fee_structures_permissions.php'
        );
        $migration->up();

        foreach (['Super Administrator', 'School Administrator'] as $roleName) {
            $this->assertSame(
                4,
                $roles[$roleName]->fresh()->permissions()
                    ->where('name', 'like', 'fee_structures.%')
                    ->count()
            );
        }

        $this->assertSame(
            ['fee_structures.create', 'fee_structures.update', 'fee_structures.view'],
            $roles['Finance Officer']->fresh()->permissions()
                ->where('name', 'like', 'fee_structures.%')
                ->orderBy('name')
                ->pluck('name')
                ->all()
        );
        $this->assertSame(
            ['fee_structures.view'],
            $roles['Principal']->fresh()->permissions()
                ->where('name', 'like', 'fee_structures.%')
                ->pluck('name')
                ->all()
        );
        $this->assertSame(
            0,
            $roles['Teacher']->permissions()
                ->where('name', 'like', 'fee_structures.%')
                ->count()
        );
    }

    private function userWithPermissions(array $permissionNames): User
    {
        return $this->userWithRolePermissions('Test Fee Structure Role', $permissionNames);
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

    private function academicContext(
        string $yearName = '2026',
        string $termName = 'Term 1',
        string $className = 'Form 1'
    ): array {
        $year = $this->createAcademicYear($yearName);
        $term = $this->createTerm($year, $termName);
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

    private function createTerm(AcademicYear $year, string $name): Term
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
        $user = User::factory()->create();
        $feeStructure = FeeStructure::create(array_replace([
            'academic_year_id' => $context['year']->id,
            'term_id' => $context['term']->id,
            'class_id' => $context['class']->id,
            'name' => 'Form 1 Term 1 Fees 2026',
            'description' => 'Standard term fees.',
            'total_amount' => 75000,
            'status' => 'draft',
            'created_by' => $user->id,
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
        ]);

        return $feeStructure;
    }

    private function validPayload(array $context, array $overrides = []): array
    {
        return array_replace([
            'academic_year_id' => $context['year']->id,
            'term_id' => $context['term']->id,
            'class_id' => $context['class']->id,
            'name' => 'Form 1 Term 1 Fees 2026',
            'description' => 'Standard term fees.',
            'status' => 'draft',
            'items' => [
                [
                    'name' => 'Tuition',
                    'amount' => '50000.00',
                    'is_mandatory' => '1',
                    'description' => 'Tuition fees.',
                ],
                [
                    'name' => 'Uniform',
                    'amount' => '25000.00',
                    'is_mandatory' => '0',
                    'description' => 'Uniform fees.',
                ],
            ],
        ], $overrides);
    }
}
