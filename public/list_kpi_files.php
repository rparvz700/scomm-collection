<?php
$folder = "D:\\D\\Automation-Innovation\\scomm-collection\\KPI_ENGINE_FILES";
header('Content-Type: text/plain; charset=utf-8');

if (!is_dir($folder)) {
    echo "Directory does not exist: $folder\n";
    // Check if there is a local copy or another folder
    exit;
}

echo "Files in $folder:\n";
$files = scandir($folder);
foreach ($files as $f) {
    if ($f !== '.' && $f !== '..') {
        echo "  $f (" . filesize($folder . '/' . $f) . " bytes)\n";
    }
}
