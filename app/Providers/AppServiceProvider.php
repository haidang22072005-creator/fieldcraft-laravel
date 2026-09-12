<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\SecondHandListing;
use App\Models\SupportTicket;
use App\Observers\PaymentObserver;
use App\Policies\OrderPolicy;
use App\Policies\SecondHandListingPolicy;
use App\Policies\SupportTicketPolicy;
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
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(SecondHandListing::class, SecondHandListingPolicy::class);
        Gate::policy(SupportTicket::class, SupportTicketPolicy::class);
        Payment::observe(PaymentObserver::class);
    }
}
