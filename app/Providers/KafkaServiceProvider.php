<?php

namespace App\Providers;

use App\Jobs\TaskCreated;
use App\Kafka\KafkaConnector;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;


class KafkaServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {

    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        $manager = $this->app['queue'];
        $manager->addConnector('kafka', function(){
            return new KafkaConnector();
        });


    }
}
