<?php
header('Content-Type: text/plain; charset=utf-8');

$dirs = [
    'd:\\laragon\\www\\scomm-collection',
    'D:\\D\\Automation-Innovation\\scomm-collection',
    'D:\\D\\Automation-Innovation\\scomm-collection\\KPI_ENGINE_FILES',
    'C:\\Users\\raihan.parvez.SUMMIT\\.gemini\\antigravity\\brain\\f74fc0b4-3eab-4c16-abe5-c14d331b1a18'
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;
    echo "Directory: $dir\n";
    $files = scandir($dir);
    foreach ($files as $f) {
        if (pathinfo($f, PATHINFO_EXTENSION) === 'xlsx' || pathinfo($f, PATHINFO_EXTENSION) === 'csv') {
            $path = $dir . '/' . $f;
            echo " - $f (" . filesize($path) . " bytes, modified: " . date('Y-m-d H:i:s', filemtime($path)) . ")\n";
        }
    }
    echo "\n";
}
