<?php

namespace Tests\Feature;

use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Tests\TestCase;

class NotExistingRoutesTest extends TestCase
{
    // EVENTS
    public function test_events_destroy(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('events.destroy', 1);
    }

    public function test_events_store(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('events.store', 1);
    }

    public function test_events_update(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('events.update', 1);
    }

    // TEMPLATE
    public function test_templates_destroy(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('templates.destroy', 1);
    }

    public function test_templates_store(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('templates.store', 1);
    }

    public function test_templates_update(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('templates.update', 1);
    }

    // MONITOR
    public function test_monitors_destroy(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('monitors.destroy', 1);
    }

    public function test_monitors_store(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('monitors.store', 1);
    }

    public function test_monitors_update(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('monitors.update', 1);
    }

    // SLIDES
    public function test_slides_destroy(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('slides.destroy', 1);
    }

    public function test_slides_store(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('slides.store', 1);
    }

    public function test_slides_update(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('slides.update', 1);
    }

    // PICS - managed entirely through their Slide, no standalone CRUD
    public function test_pics_index(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('pics.index');
    }

    public function test_pics_create(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('pics.create');
    }

    public function test_pics_store(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('pics.store');
    }

    public function test_pics_edit(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('pics.edit', 1);
    }

    public function test_pics_update(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('pics.update', 1);
    }

    public function test_pics_destroy(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('pics.destroy', 1);
    }

    // VIDEOS - managed entirely through their Slide, no standalone CRUD
    public function test_videos_index(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('videos.index');
    }

    public function test_videos_create(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('videos.create');
    }

    public function test_videos_store(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('videos.store');
    }

    public function test_videos_edit(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('videos.edit', 1);
    }

    public function test_videos_update(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('videos.update', 1);
    }

    public function test_videos_destroy(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('videos.destroy', 1);
    }

    // EVENTS_IMPORT
    public function test_events_import_destroy(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('eventsImports.destroy', 1);
    }

    public function test_events_import_store(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('eventsImports.store', 1);
    }

    public function test_events_import_update(): void
    {
        $this->expectException(RouteNotFoundException::class);
        route('eventsImports.update', 1);
    }
}
