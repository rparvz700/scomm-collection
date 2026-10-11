<?php
header('Content-Type: text/plain; charset=utf-8');

$excelFile = "D:\\D\\Automation-Innovation\\scomm-collection\\Information for OS Automation.xlsx";

try {
    $zip = new ZipArchive();
    if ($zip->open($excelFile) !== TRUE) {
        echo "Could not open Excel zip archive.\n";
        exit;
    }

    $wbContent = $zip->getFromName('xl/workbook.xml');
    $wbXml = simplexml_load_string($wbContent);

    echo "Sheet names in 'Information for OS Automation.xlsx':\n";
    foreach ($wbXml->sheets->sheet as $sheet) {
        echo " - " . (string)$sheet['name'] . "\n";
    }

    $zip->close();
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
