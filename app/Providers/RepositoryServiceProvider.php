<?php

namespace App\Providers;

use App\Contracts\Repositories\ActivityLogRepositoryInterface;
use App\Contracts\Repositories\GymSettingRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Repositories\ActivityLogRepository;
use App\Repositories\GymSettingRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        UserRepositoryInterface::class => UserRepository::class,
        GymSettingRepositoryInterface::class => GymSettingRepository::class,
        ActivityLogRepositoryInterface::class => ActivityLogRepository::class,
    ];

    public function register(): void
    {
        foreach ($this->bindings as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }
}
