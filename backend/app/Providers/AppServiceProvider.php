<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\AgencyProfile;
use App\Models\Client;
use App\Models\ClientProject;
use App\Models\MasterBillingRate;
use App\Models\User;
use App\Models\UserDevice;
use App\Policies\AgencyProfilePolicy;
use App\Policies\ClientPolicy;
use App\Policies\ClientProjectPolicy;
use App\Policies\MasterBillingRatePolicy;
use App\Policies\SessionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(UserDevice::class, SessionPolicy::class);
        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(ClientProject::class, ClientProjectPolicy::class);
        Gate::policy(MasterBillingRate::class, MasterBillingRatePolicy::class);
        Gate::policy(AgencyProfile::class, AgencyProfilePolicy::class);
    }
}
