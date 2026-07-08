<?php

namespace OccTherapist\FormRequestValidationForFilament\Tests;

use OccTherapist\FormRequestValidationForFilament\FormRequestValidationServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            FormRequestValidationServiceProvider::class,
        ];
    }
}
