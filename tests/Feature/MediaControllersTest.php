<?php

namespace Tests\Feature;

use App\Events\SecurityAuditEvent;
use App\Http\Controllers\SlideController;
use App\Livewire\CreateMenu;
use App\Models\Menu;
use App\Models\Monitor;
use App\Models\Picture;
use App\Models\Realm;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MediaControllersTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Realm $realm;

    private Monitor $monitor;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->realm = Realm::factory()->create();
        $this->user = User::factory()->create([
            'user_type' => 'member',
            'realm_id' => $this->realm->id,
        ]);
        $this->monitor = Monitor::factory()->create([
            'realm_id' => $this->realm->id,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);
    }

    // --- PICTURE CONTROLLER TESTS ---
    // Pictures no longer have standalone index/create/edit/destroy routes -
    // they are managed entirely through their Slide (see LivewireComponentsTest).
    // Only pics.show and the source-management endpoints remain.

    public function test_picture_sources_management(): void
    {
        $picture = Picture::factory()->create([
            'user_id' => $this->user->id,
            'realm_id' => $this->realm->id,
        ]);
        $firstSource = $picture->sources->first();

        $slide = Schedule::factory()->create([
            'scheduleable_type' => 'PI',
            'scheduleable_id' => $picture->id,
            'realm_id' => $this->realm->id,
            'user_id' => $this->user->id,
        ]);

        // 1. Add second source (redirects back to wherever the request came
        // from, i.e. the slide edit page)
        $secondImage = UploadedFile::fake()->image('second.png', 1920, 1080);
        $response = $this->from(route('slides.edit', $slide->id))->post(route('pics.storeSource', $picture->id), [
            'upload' => $secondImage,
        ]);
        $response->assertRedirect(route('slides.edit', $slide->id));
        $this->assertEquals(2, $picture->sources()->count());

        $secondSource = $picture->sources()->where('id', '!=', $firstSource->id)->first();

        // 2. Update source clock location
        $response = $this->put(route('pics.updateSource', $secondSource->id), [
            'clock_location' => 8,
        ]);
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
        $this->assertEquals(8, $secondSource->fresh()->clock_location);

        // 3. Delete second source
        $response = $this->delete(route('pics.destroySource', $secondSource->id));
        $response->assertRedirect();
        $this->assertEquals(1, $picture->sources()->count());

        // 4. Try deleting the only remaining source (should be rejected)
        $response = $this->delete(route('pics.destroySource', $firstSource->id));
        $response->assertRedirect();
        $this->assertEquals(1, $picture->sources()->count());
    }

    // --- VIDEO CONTROLLER TESTS ---
    // Videos no longer have standalone index/create/edit/destroy routes -
    // they are managed entirely through their Slide (see LivewireComponentsTest).
    // Only videos.show remains.

    // --- MENU CONTROLLER TESTS ---

    public function test_menu_index_and_create_views(): void
    {
        $response = $this->get(route('menus.index'));
        $response->assertStatus(200);
        $response->assertViewIs('menus.index');

        $response = $this->get(route('menus.create'));
        $response->assertStatus(200);
        $response->assertViewIs('menus.create');
    }

    public function test_menu_store_and_update_and_show(): void
    {
        $jsonFile = UploadedFile::fake()->createWithContent('menu.json', json_encode(['items' => ['Cocktail A', 'Beer B']]));

        Livewire::actingAs($this->user)
            ->test(CreateMenu::class)
            ->set('name', 'Drinks Menu')
            ->set('upload', $jsonFile)
            ->set('monitors', [$this->monitor->id])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('menus.index'));

        $menu = Menu::where('name', 'Drinks Menu')->first();
        $this->assertNotNull($menu);
        $this->assertEquals($this->realm->id, $menu->realm_id);
        Storage::disk('public')->assertExists(config('ads.menu_basepath').$menu->path);

        // Edit view
        $response = $this->get(route('menus.edit', $menu->id));
        $response->assertStatus(200);

        // Show endpoint
        $response = $this->get(route('menus.show', $menu->id));
        $response->assertStatus(200);

        // Update menu
        $updatedJson = json_encode(['items' => ['Cocktail Premium', 'Wine C']]);
        $response = $this->put(route('menus.update', $menu->id), [
            'name' => 'Updated Drinks Menu',
            'menu_content' => $updatedJson,
            'monitors' => [$this->monitor->id],
        ]);

        $response->assertRedirect(route('menus.index'));
        $menu->refresh();
        $this->assertEquals('Updated Drinks Menu', $menu->name);
        $this->assertEquals($updatedJson, Storage::disk('public')->get(config('ads.menu_basepath').$menu->path));
    }

    public function test_menu_destroy_deletes_file_and_dispatches_audit_event(): void
    {
        Event::fake([SecurityAuditEvent::class]);

        $menu = Menu::factory()->create([
            'user_id' => $this->user->id,
            'realm_id' => $this->realm->id,
            'name' => 'Menu To Delete',
            'path' => 'menu_delete.json',
        ]);
        Storage::disk('public')->put(config('ads.menu_basepath').$menu->path, '{"test":true}');

        $response = $this->delete(route('menus.destroy', $menu->id));
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $this->assertDatabaseMissing('menus', ['id' => $menu->id]);
        Storage::disk('public')->assertMissing(config('ads.menu_basepath').$menu->path);

        Event::assertDispatched(SecurityAuditEvent::class, function (SecurityAuditEvent $event) use ($menu) {
            return $event->action === 'menu.deleted' && $event->context['menu_id'] === $menu->id;
        });
    }

    // --- SLIDE CONTROLLER (PICTURE & VIDEO SLIDES, UNIFIED) ---

    public function test_slide_controller_views(): void
    {
        $picture = Picture::factory()->create([
            'user_id' => $this->user->id,
            'realm_id' => $this->realm->id,
        ]);
        $video = Video::factory()->create([
            'user_id' => $this->user->id,
            'realm_id' => $this->realm->id,
        ]);

        $picSlide = Schedule::factory()->create([
            'scheduleable_type' => 'PI',
            'scheduleable_id' => $picture->id,
            'realm_id' => $this->realm->id,
            'user_id' => $this->user->id,
        ]);

        $vidSlide = Schedule::factory()->create([
            'scheduleable_type' => 'VI',
            'scheduleable_id' => $video->id,
            'realm_id' => $this->realm->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->get(route('slides.index'));
        $response->assertStatus(200);

        $this->withoutExceptionHandling();
        $response = $this->get(route('slides.create'));
        $response->assertStatus(200);

        $response = $this->get(route('slides.edit', $picSlide->id));
        $response->assertStatus(200);

        $response = $this->get(route('slides.edit', $vidSlide->id));
        $response->assertStatus(200);
    }

    public function test_get_scheduled_media_on_monitor_helpers(): void
    {
        $picture = Picture::factory()->create([
            'user_id' => $this->user->id,
            'realm_id' => $this->realm->id,
        ]);
        $video = Video::factory()->create([
            'user_id' => $this->user->id,
            'realm_id' => $this->realm->id,
        ]);

        // Active schedules
        Schedule::factory()->create([
            'scheduleable_type' => 'PI',
            'scheduleable_id' => $picture->id,
            'realm_id' => $this->realm->id,
            'user_id' => $this->user->id,
            'start' => now()->subDay()->format('Y-m-d'),
            'end' => now()->addDay()->format('Y-m-d'),
            'disabled' => false,
        ]);

        Schedule::factory()->create([
            'scheduleable_type' => 'VI',
            'scheduleable_id' => $video->id,
            'realm_id' => $this->realm->id,
            'user_id' => $this->user->id,
            'start' => now()->subDay()->format('Y-m-d'),
            'end' => now()->addDay()->format('Y-m-d'),
            'disabled' => false,
        ]);

        $this->monitor->show_pictures = true;
        $this->monitor->show_videos = true;
        $this->monitor->stats = [
            'screen' => [
                'availWidth' => 1920,
                'availHeight' => 1080,
            ],
        ];
        $this->monitor->save();

        $pics = SlideController::getScheduledPicturesOnMonitor($this->monitor);
        $this->assertNotNull($pics);
        $this->assertGreaterThanOrEqual(1, $pics->count());

        $videos = SlideController::getScheduledVideosOnMonitor($this->monitor);
        $this->assertNotNull($videos);
        $this->assertGreaterThanOrEqual(1, $videos->get()->count());
    }
}
