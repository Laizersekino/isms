<?php

namespace Tests\Feature\Frontend;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class DesignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_dashboard_uses_school_branding_and_avatar_initials_without_a_logo(): void
    {
        config([
            'school.name' => 'Northside School',
            'school.logo' => null,
        ]);
        $user = User::factory()->create(['name' => 'Alex Morgan']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Northside School')
            ->assertSee('>AM</span>', false)
            ->assertSee('favicon.svg');
        $this->assertFileExists(public_path('favicon.svg'));
    }

    public function test_all_layouts_link_to_configured_svg_and_ico_favicons(): void
    {
        config(['school.favicon' => 'branding/isms.svg']);
        $user = User::factory()->create();
        $dashboard = $this->actingAs($user)->get(route('dashboard'))->getContent();
        $login = $this->get(route('login'))->getContent();
        $print = view('layouts.print')->render();
        $pdf = view('layouts.pdf')->render();
        $svgLink = '<link rel="icon" type="image/svg+xml" href="'.asset('branding/isms.svg').'">';
        $icoLink = '<link rel="alternate icon" href="'.asset('favicon.ico').'">';

        foreach ([$dashboard, $login, $print, $pdf] as $html) {
            $this->assertStringContainsString($svgLink, $html);
            $this->assertStringContainsString($icoLink, $html);
            $this->assertLessThan(strpos($html, $icoLink), strpos($html, $svgLink));
        }

        $favicon = file_get_contents(public_path('favicon.ico'));
        $this->assertNotFalse($favicon);
        $this->assertSame("\0\0\x01\0\x01\0", substr($favicon, 0, 6));
        $this->assertGreaterThan(22, strlen($favicon));
    }

    public function test_dashboard_renders_the_configured_school_logo(): void
    {
        config([
            'school.name' => 'Northside School',
            'school.logo' => 'favicon.svg',
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('src="'.asset('favicon.svg').'"', false)
            ->assertSee('alt="Northside School"', false);
    }

    public function test_shared_navigation_shows_accessible_header_and_sidebar_icons(): void
    {
        $user = $this->userWithPermission('announcements.view');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('aria-label="Announcements"', false)
            ->assertSee('aria-label="'.$user->name.'"', false)
            ->assertSee('m3 10 9-7 9 7v10', false)
            ->assertSee('M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9', false)
            ->assertSee('M10 17l5-5-5-5m5 5H3', false)
            ->assertSee('Announcements');
    }

    public function test_button_component_renders_create_edit_delete_and_save_icons(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-button icon="plus">Add</x-button>
            <x-button icon="pencil-square">Edit</x-button>
            <x-button icon="trash" variant="danger">Delete</x-button>
            <x-button icon="arrow-down-tray">Save</x-button>
        BLADE);

        $this->assertStringContainsString('Add', $html);
        $this->assertStringContainsString('Edit', $html);
        $this->assertStringContainsString('Delete', $html);
        $this->assertStringContainsString('Save', $html);
        $this->assertStringContainsString('bg-danger-600', $html);
        $this->assertStringContainsString('M12 5v14m-7-7h14', $html);
        $this->assertStringContainsString('m16 3 5 5-9 9-5 1 1-5 8-10Z', $html);
        $this->assertStringContainsString('M3 6h18m-2 0-1 14H6L5 6', $html);
        $this->assertStringContainsString('M12 3v12m0 0 4-4m-4 4-4-4', $html);
    }

    public function test_avatar_and_empty_state_components_render_their_fallback_content(): void
    {
        $avatar = Blade::render('<x-avatar name="Taylor Jordan" size="lg" />');
        $emptyState = Blade::render(
            '<x-empty-state title="No books found" message="Try another search." icon="book-open">Clear filters</x-empty-state>'
        );

        $this->assertStringContainsString('>TJ</span>', $avatar);
        $this->assertStringContainsString('No books found', $emptyState);
        $this->assertStringContainsString('Try another search.', $emptyState);
        $this->assertStringContainsString('Clear filters', $emptyState);
        $this->assertStringContainsString('aria-hidden="true"', $emptyState);
    }

    private function userWithPermission(string $permissionName): User
    {
        $user = User::factory()->create(['name' => 'Jordan Lee']);
        $role = Role::create(['name' => 'Design Test Role']);
        $permission = Permission::firstOrCreate(['name' => $permissionName]);

        $role->permissions()->attach($permission->id);
        $user->roles()->attach($role->id);

        return $user;
    }
}
