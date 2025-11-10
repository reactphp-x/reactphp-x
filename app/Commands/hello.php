<?php

require __DIR__ . '/../../vendor/autoload.php';

use ReactphpX\Log\Log;

$container = require __DIR__ . '/../../bootstrap/app.php';

Log::info('Hello command at ' . date('Y-m-d H:i:s'));

echo 'Hello command at ' . date('Y-m-d H:i:s') . "\n";


