<?php

namespace Tests;

use App\Models\User;
use App\Providers\TravelRequestServiceProvider;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Log;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Habilitar o modo de depuração para ver detalhes de erros
        $this->withoutExceptionHandling();
        
        // Registrar o service provider para os testes
        $this->app->register(TravelRequestServiceProvider::class);
        
        // Definir método isAdmin para o User model durante testes
        if (!method_exists(User::class, 'isAdmin')) {
            User::macro('isAdmin', function () {
                return $this->role === 'admin';
            });
        }
        
        // Configurar handler de erros personalizado para os testes
        $this->app->singleton('exception.handler', function ($app) {
            $handler = new \Illuminate\Foundation\Exceptions\Handler($app);
            
            $handler->reportable(function (\Throwable $e) {
                Log::error("Erro durante teste: " . $e->getMessage());
                Log::error($e->getTraceAsString());
            });
            
            return $handler;
        });
    }
}