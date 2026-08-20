<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

$user = User::first();
Auth::login($user);

try {
    echo "Rendering dashboard...\n";
    $controller = new \App\Http\Controllers\DashboardOptimizedController();
    $request = \Illuminate\Http\Request::create('/dashboard-optimized', 'GET');
    $response = $controller($request);
    echo "Success! View renders fine.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
