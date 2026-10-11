<?php
header('Content-Type: text/plain; charset=utf-8');

$dir = "D:\\D\\Automation-Innovation\\scomm-collection\\KPI_ENGINE_FILES";
if (is_dir($dir)) {
    echo "Files in $dir starting with 17:\n";
    $files = scandir($dir);
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        if (strpos($f, '17') === 0 || strpos($f, '17') !== false) {
            echo " - $f (" . filesize($dir . '/' . $f) . " bytes)\n";
        }
    }
} else {
    echo "Directory does not exist: $dir\n";
}
