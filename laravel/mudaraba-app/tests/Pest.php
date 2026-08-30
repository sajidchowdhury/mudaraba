<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Bind all tests to the Laravel TestCase + auto-refresh the DB
uses(TestCase::class, RefreshDatabase::class)->in('Feature', 'Unit');
