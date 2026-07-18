<?php
$stage = \App\Models\FreeTrialEducationalStage::withCount('grades')->first();
echo json_encode($stage->toArray(), JSON_PRETTY_PRINT);
