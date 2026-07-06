<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
        'App\Reservation' => 'App\Policies\ReservationPolicy', /* L35 */
        'App\Photo' => 'App\Policies\PhotoPolicy', /* L39 */
        'App\TouristObject' => 'App\Policies\ObjectPolicy', /* L43 */
        'App\Article' => 'App\Policies\ArticlePolicy', /* L45 */
        'App\Room' => 'App\Policies\RoomPolicy' /* L47 */
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
