<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$stage = \App\Models\FreeTrialEducationalStage::withCount('grades')->first();
echo "STAGE:\n";
echo json_encode($stage ? $stage->toArray() : ['error' => 'no stage found'], JSON_PRETTY_PRINT);

echo "\nGRADE:\n";
$grade = \App\Models\FreeTrialGrade::withCount('subjects')->first();
echo json_encode($grade ? $grade->toArray() : ['error' => 'no grade found'], JSON_PRETTY_PRINT);
