<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\App;  

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        /* L49 */
        View::composer('backend.*', '\App\Enjoythetrip\ViewComposers\BackendComposer');
        
        /* L16 */
        View::composer('frontend.*', function ($view) {
            $view->with('placeholder', asset('images/placeholder.jpg'));
            });
            
        /* L34 */  
        if (App::environment('local'))
        {
            
           View::composer('*', function ($view) {
            $view->with('novalidate', 'novalidate');
            });
  
        }
        else
        {
            View::composer('*', function ($view) {
            $view->with('novalidate', null);
            });
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        /* We need to register the implementation of this interface in the service container. This container is the class in with object use in over application are store. So we don't to create the instance of the needed class our self we only refert to it, Laravel create the instance of this class. Hear as we can see to register the object into the container we use the bind methode L13,55 */
        if (App::environment('local'))
        {
            
            /* Lecture 13 */
            $this->app->bind(\App\Enjoythetrip\Interfaces\FrontendRepositoryInterface::class,function()
            {            
                return new \App\Enjoythetrip\Repositories\FrontendRepository;
            });
  
        }
        else
        {
            
            $this->app->bind(\App\Enjoythetrip\Interfaces\FrontendRepositoryInterface::class,function()
            {            
                return new \App\Enjoythetrip\Repositories\CachedFrontendRepository;
            });  

        }

        /* L27 */
        $this->app->bind(\App\Enjoythetrip\Interfaces\BackendRepositoryInterface::class,function($app)
        {
            return $app->make(\App\Enjoythetrip\Repositories\BackendRepository::class);
        });
    }
}
