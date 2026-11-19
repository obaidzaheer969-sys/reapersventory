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

    public function test_pricing_page_shows_brand_text_below_logo(): void
    {
        $this->get(route('pricing'))
            ->assertOk()
            ->assertSee('<img src="'.asset('images/reapersventory-logo.png').'" alt="'.e(config('earning.brand')).'" class="h-12 w-auto">', false)
            ->assertSee('<span class="mt-1 font-display font-bold tracking-tight text-base text-white">'.e(config('earning.brand')).'</span>', false);
    }

    public function test_public_pages_use_earning_seo_metadata(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<title>'.e(config('earning.meta_title')).'</title>', false)
            ->assertSee('<meta name="description" content="'.config('earning.meta_description').'">', false)
            ->assertDontSee('Inventory & Billing SaaS', false);

        $this->get(route('pricing'))
            ->assertOk()
            ->assertSee('<title>Pricing — '.config('earning.brand').'</title>', false)
            ->assertSee('<meta name="description" content="'.config('earning.meta_description').'">', false);
    }
}
