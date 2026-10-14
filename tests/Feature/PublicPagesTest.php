<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_and_pricing_pages_render(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('ReapersVentory', false);

        $this->get(route('pricing'))
            ->assertOk()
            ->assertSee('Plans will appear here', false);
    }
}
