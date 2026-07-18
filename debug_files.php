<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "FILES IN THUMBNAILS:\n";
$files = \Illuminate\Support\Facades\Storage::disk('thumbnails')->allFiles();
foreach($files as $file) {
    echo $file . "\n";
}
echo "DONE.";
