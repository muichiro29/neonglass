<?php

namespace MuichiroDev\NeonGlass\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;
use MuichiroDev\NeonGlass\Http\Controllers\AssetController;

class NeonGlassRoutesProvider extends RouteServiceProvider
{
    public function boot(): void
    {
        $this->routes(function () {
            Route::get('neonglass/assets/{path}', AssetController::class)
                ->where('path', '.+')
                ->name('neonglass.asset');
        });
    }
}
