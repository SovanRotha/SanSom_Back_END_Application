<?php

namespace Tests\Feature;

use Tests\TestCase;

class CategoryAuthTest extends TestCase
{
    public function test_guest_cannot_fetch_categories(): void
    {
        $response = $this->getJson('/categories');

        $response->assertStatus(401);
    }
}
