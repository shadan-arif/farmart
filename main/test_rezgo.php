<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$api = app(Botble\RezgoConnector\Services\RezgoApiService::class);
Setting::set('rezgo_cid', '36752');
Setting::set('rezgo_api_key', Illuminate\Support\Facades\Crypt::encryptString('0T-6B3D-7S3P-3D5K'));

$res = $api->testConnection();
print_r($res);
