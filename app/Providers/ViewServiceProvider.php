<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
  /**
   * Register services.
   */
  public function register(): void
  {
    //
  }

  /**
   * Bootstrap services.
   */
  public function boot(): void
  {
    // Using Closure based view composers...
    View::composer('*', function ($view) {
      $view
        ->with('currentUser', currentUser())
        ->with('currentInstitution', currentInstitution());
    });
  }
}
