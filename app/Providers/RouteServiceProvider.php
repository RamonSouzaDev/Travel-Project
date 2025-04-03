<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * O namespace do seu controlador.
     *
     * @var string|null
     */
    // Se quiser usar prefixo automático de namespace nos controllers (opcional)
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Caminho para a "home" da aplicação.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define as rotas da aplicação.
     */
    public function boot(): void
    {
        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
