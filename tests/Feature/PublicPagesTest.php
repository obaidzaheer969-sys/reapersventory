<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_redirects_to_login_and_pricing_page_renders(): void
    {
        $this->get(route('home'))
            ->assertRedirect(route('login'));

        $this->get(route('pricing'))
            ->assertOk()
            ->assertSee('Plans will appear here', false);
    }
}
