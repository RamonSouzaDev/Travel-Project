<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * As políticas de autorização do aplicativo.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Registra quaisquer serviços de autenticação/autorização.
     */
    public function boot(): void
    {
        // Configurar JWT Auth
        Auth::extend('jwt', function ($app, $name, array $config) {
            return new JWTGuard(
                $app->make(\PHPOpenSourceSaver\JWTAuth\JWT::class),
                $app['auth']->createUserProvider($config['provider']),
                $app['request']
            );
        });
    }
}
