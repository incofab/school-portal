<?php

namespace App\Providers;

use App\Models\Institution;
use App\Support\MorphableHandler;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
  /**
   * The path to the "home" route for your application.
   *
   * This is used by Laravel authentication to redirect users after login.
   *
   * @var string
   */
  public const HOME = '/dashboard';

  /**
   * The controller namespace for the application.
   *
   * When present, controller route declarations will automatically be prefixed with this namespace.
   *
   * @var string|null
   */
  // protected $namespace = 'App\\Http\\Controllers';

  /**
   * Define your route model bindings, pattern filters, etc.
   *
   * @return void
   */
  public function boot()
  {
    // Route::model('institution', Institution::class);

    $this->configureRateLimiting();

    Route::bind('morphable', function ($value, $route) {
      return (new MorphableHandler())->getModel($value);
    });

    $this->routes(function () {
      Route::prefix('api')
        ->middleware('api')
        ->namespace($this->namespace)
        ->group(base_path('routes/api.php'));

      Route::middleware('web')
        ->namespace($this->namespace)
        ->group(base_path('routes/web.php'));

      Route::middleware(['web', 'auth', 'institution.user'])
        ->prefix('{institution}')
        ->name('institutions.')
        ->group(base_path('routes/institution.php'));

      Route::middleware(['web', 'auth', 'institution.user'])
        ->prefix('{institution}/ccd')
        ->name('institutions.')
        ->group(base_path('routes/ccd.php'));

      Route::middleware(['web', 'auth', 'manager'])
        ->prefix('manager')
        ->name('managers.')
        ->group(base_path('routes/manager.php'));

      // Route::middleware(['web', 'auth', 'partner'])
      //   ->prefix('partner')
      //   ->name('partners.')
      //   ->group(base_path('routes/partner.php'));
    });
  }

  /**
   * Configure the rate limiters for the application.
   *
   * @return void
   */
  protected function configureRateLimiting()
  {
    RateLimiter::for('api', function (Request $request) {
      return Limit::perMinute(60)->by(
        optional($request->user())->id ?: $request->ip()
      );
    });
  }
}
