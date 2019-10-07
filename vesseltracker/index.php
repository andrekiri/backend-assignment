<?php

require 'vendor/autoload.php';

// Initialize application and container
$app = require_once __DIR__ . '/initApp.php';

// Run app
$app->run();