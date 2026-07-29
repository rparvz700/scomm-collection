<?php
header('Content-Type: text/plain; charset=utf-8');

function findXlsxFiles($dir, &$results = []) {
    if (!is_dir($dir)) return $results;
    $files = scandir($dir);
    foreach ($files as $value) {
        $path = $dir . DIRECTORY_SEPARATOR . $value;
        if (!is_dir($path)) {
            if (pathinfo($path, PATHINFO_EXTENSION) === 'xlsx') {
                $results[] = [
                    'path' => $path,
                    'size' => filesize($path)
                ];
            }
        } else if ($value != "." && $value != "..") {
            // Avoid deep scanning system directories, only scan laragon www and adjacent if accessible
            if (strpos($path, '.git') === false && strpos($path, 'vendor') === false && strpos($path, 'node_modules') === false) {
                findXlsxFiles($path, $results);
            }
        }
    }
    return $results;
}

echo "Scanning D:\\laragon\\www for .xlsx files...\n";
$results = findXlsxFiles("D:\\laragon\\www");
foreach ($results as $res) {
    echo " - " . $res['path'] . " (" . $res['size'] . " bytes)\n";
}

echo "\nScanning adjacent directories...\n";
$results2 = findXlsxFiles("D:\\D");
foreach ($results2 as $res) {
    echo " - " . $res['path'] . " (" . $res['size'] . " bytes)\n";
}
