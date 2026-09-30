<?php

declare(strict_types = 1);

use Kirby\Cms\App;

require_once __DIR__ . '/../vendor/autoload.php';

// Constructing a Kirby\Cms\File/Page lazily bootstraps a default App instance
// (via App::instance()) for blueprint resolution, which by default registers
// Whoops as a global error/exception handler and never unregisters it. That
// trips PHPUnit's "did not remove its own error handlers" risky-test check.
// This is Kirby's own sanctioned switch for disabling Whoops in CI/tests.
App::$enableWhoops = false;

// Register the plugin (options, snippets, file methods) once for the whole run.
// Integration tests create a fresh App per test; plugin registrations are static
// and survive that, so this must not be repeated (or undone via App::destroy()).
require_once __DIR__ . '/../index.php';
