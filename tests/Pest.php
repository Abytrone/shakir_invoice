<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "Tests\TestCase". You may use the
| following method to accept a different test case:
|
*/

uses(TestCase::class)->in('Unit');

uses(TestCase::class, RefreshDatabase::class)->in('Feature');
