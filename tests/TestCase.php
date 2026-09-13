<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // CI no compila assets (no hay public/build/manifest.json); sin esto,
        // cualquier vista que use @vite truena con ViteManifestNotFoundException.
        $this->withoutVite();
    }
}
