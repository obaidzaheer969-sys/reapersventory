<?php

namespace Tests\Unit;

use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_generates_unique_slugs(): void
    {
        $first = Business::factory()->create(['name' => 'Alpha Store', 'slug' => 'alpha-store']);
        $second = Business::query()->create([
            'name' => 'Alpha Store',
            'email' => 'alpha2@example.com',
        ]);

        $this->assertSame('alpha-store', $first->slug);
        $this->assertNotSame($first->slug, $second->slug);
        $this->assertStringStartsWith('alpha-store', $second->slug);
    }
}
