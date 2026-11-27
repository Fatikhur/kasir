<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_unauthenticated_root_redirects_to_login_and_login_page_is_accessible(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get('/login')->assertStatus(200);
    }
}
