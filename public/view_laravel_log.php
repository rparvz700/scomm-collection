<?php
$logFile = __DIR__ . '/../storage/logs/laravel.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -40);
    echo "=== Laravel Log (Last 40 lines) ===\n";
    foreach ($lastLines as $l) {
        echo $l;
    }
} else {
    echo "No Laravel log found at $logFile\n";
}
