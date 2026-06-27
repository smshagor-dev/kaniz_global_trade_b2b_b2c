<?php

namespace App\Providers;

use App\Observers\SearchIndexObserver;
use App\Services\AI\AIManager;
use App\Services\Search\SearchManager;
use App\Services\Search\SearchModelRegistry;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Bootstrap any application services.
   *
   * @return void
   */
  public function boot()
  {
      Schema::defaultStringLength(191);
      Paginator::useBootstrap();

      foreach (SearchModelRegistry::models() as $modelClass) {
          $modelClass::observe(SearchIndexObserver::class);
      }
  }

  /**
   * Register any application services.
   *
   * @return void
   */
  public function register()
  {
    $this->app->singleton(AIManager::class, fn () => new AIManager());
    $this->app->singleton(SearchManager::class, fn () => new SearchManager());
  }
}
