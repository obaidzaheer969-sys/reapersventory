<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_index_returns_version_information(): void
    {
        $this->getJson('/api/v1')
            ->assertOk()
            ->assertJsonPath('name', 'ReapersInventory API')
            ->assertJsonPath('version', 'v1');
    }
}
