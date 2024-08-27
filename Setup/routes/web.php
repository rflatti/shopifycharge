<?php
use Illuminate\Support\Facades\Route;
use RFlatti\ShopifyPlans\Controllers\ChargeController;


Route::prefix('shopify')->group(function (){
    Route::get('/subscribe/plan_id/{plan_id}', [ChargeController::class, 'charge']);
});
