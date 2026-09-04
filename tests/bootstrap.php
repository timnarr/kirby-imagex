<?php

use Kirby\Cms\App;

require_once __DIR__ . '/../vendor/autoload.php';

// Constructing a Kirby\Cms\File/Page lazily bootstraps a default App instance
// (via App::instance()) for blueprint resolution, which by default registers
// Whoops as a global error/exception handler and never unregisters it. That
// trips PHPUnit's "did not remove its own error handlers" risky-test check.
// This is Kirby's own sanctioned switch for disabling Whoops in CI/tests.
App::$enableWhoops = false;
