<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_redirects_to_login_and_pricing_page_is_removed(): void
    {
        $this->get(route('home'))
            ->assertRedirect(route('login'));

        $this->get('/pricing')
            ->assertNotFound();
    }

    public function test_public_pages_use_earning_seo_metadata(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<title>'.e(config('earning.meta_title')).'</title>', false)
            ->assertSee('<meta name="description" content="'.config('earning.meta_description').'">', false)
            ->assertDontSee('Inventory & Billing SaaS', false);
    }
}
