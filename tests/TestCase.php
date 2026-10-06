<?php

namespace Tests;

use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests must not depend on a Vite build being present.
        $this->withoutVite();

        $this->seed(UserTypeSeeder::class);
    }
}
