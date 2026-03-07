<?php

use Illuminate\Support\Facades\Route;
use Plugins\Reaction\Controllers\ReactionController;

Route::middleware('auth:sanctum')->group(function () {
    // React to a post or comment
    // type: like | bless | amen | pray | love
    Route::post('/{reactable_type}/{reactable_id}/react',   [ReactionController::class, 'react']);
    Route::delete('/{reactable_type}/{reactable_id}/react', [ReactionController::class, 'unreact']);
    Route::get('/{reactable_type}/{reactable_id}/reactions',[ReactionController::class, 'index']);
});
