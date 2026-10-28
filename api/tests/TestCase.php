<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Symfony ger testrequests Accept-Language "en-us,en;q=0.5" som standard. Utan den här
        // raden skulle API:et svara på engelska i varje test som inte uttryckligen väljer språk.
        $this->withServerVariables(['HTTP_ACCEPT_LANGUAGE' => '']);
    }
}
