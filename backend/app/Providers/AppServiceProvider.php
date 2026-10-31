<?php

namespace App\Providers;

use App\Models\Logistics\GoodsIssue;
use App\Models\Logistics\StockMutation;
use App\Models\Logistics\StockOpname;
use App\Policies\Logistics\GoodsIssuePolicy;
use App\Policies\Logistics\StockMutationPolicy;
use App\Policies\Logistics\StockOpnamePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(StockMutation::class, StockMutationPolicy::class);
        Gate::policy(StockOpname::class, StockOpnamePolicy::class);
        Gate::policy(GoodsIssue::class, GoodsIssuePolicy::class);
        Route::model('stock_opname', StockOpname::class);
        Route::model('goods_issue', GoodsIssue::class);
    }
}
