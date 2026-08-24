<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\Faq;
use App\Models\PaymentReference;
use App\Policies\ActivityLogPolicy;
use App\Policies\FaqPolicy;
use App\Policies\PaymentReferencePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        ActivityLog::class => ActivityLogPolicy::class,
        Faq::class => FaqPolicy::class,
        PaymentReference::class => PaymentReferencePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        $this->bypassPermissionsIfUserIsAdmin();
    }

    private function bypassPermissionsIfUserIsAdmin()
    {
        Gate::before(function ($user, $ability, $model = null) {
            if (isset($user) && $user->isAdmin()) {
                return true;
            }
        });
    }
}
