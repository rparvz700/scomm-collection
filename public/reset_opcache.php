<?php
header('Content-Type: text/plain; charset=utf-8');
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "OPcache successfully reset.\n";
    } else {
        echo "OPcache reset failed.\n";
    }
} else {
    echo "OPcache extension not enabled/installed.\n";
}
