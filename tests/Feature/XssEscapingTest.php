<?php

namespace Tests\Feature;

use App\Livewire\EventsList;
use App\Models\Event;
use App\Models\Realm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class XssEscapingTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_owner_name_is_escaped_in_events_list(): void
    {
        $realm = Realm::factory()->create();
        $owner = User::factory()->create([
            'realm_id' => $realm->id,
            'name' => '<script>alert(1)</script>',
        ]);

        $event = Event::factory()->create([
            'user_id' => $owner->id,
            'realm_id' => $realm->id,
        ]);

        $admin = User::factory()->create([
            'user_type' => 'admin',
            'realm_id' => $realm->id,
        ]);

        Livewire::actingAs($admin)
            ->test(EventsList::class)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('<script>alert(1)</script>', true);

        $this->assertNotNull($event);
    }
}
