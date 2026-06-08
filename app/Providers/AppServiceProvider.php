<?php

namespace App\Providers;

use App\Observers\ModelAuditObserver;
use App\Support\Audit\ModelAuditRegistry;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   *
   * @return void
   */
  public function register()
  {
  }

  /**
   * Bootstrap any application services.
   *
   * @return void
   */
  public function boot()
  {
    Relation::enforceMorphMap(MorphMap::MAP);

    foreach (ModelAuditRegistry::models() as $model) {
      $model::observe(ModelAuditObserver::class);
    }

    $this->allowMultiDomain();
  }

  /**
   * Set cookies base on the calling domain since multiple domains will be pointed to this application
   */
  private function allowMultiDomain()
  {
    $host = request()->getHost();
    $name = Str::slug($host, '_') . '_session';
    Config::set('session.cookie', $name);
  }
}
