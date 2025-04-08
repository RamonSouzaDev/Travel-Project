<?php

namespace Tests;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

trait DatabaseTest
{
    /**
     * Configuração do banco de dados para testes
     */
    public function setupTestDatabase()
    {
        // Configurar SQLite em memória
        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        
        // Garantir que estamos usando a conexão SQLite em memória
        DB::purge();
        
        // Criar as tabelas necessárias
        $this->createTables();
    }
    
    /**
     * Cria as tabelas necessárias para os testes
     */
    protected function createTables()
    {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('user');
            $table->rememberToken();
            $table->timestamps();
        });
        
        Schema::create('travel_requests', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('destination');
            $table->date('departure_date');
            $table->date('return_date');
            $table->string('status')->default('solicitado');
            $table->text('reason_for_cancellation')->nullable();
            $table->timestamps();
            
            // Não usar foreign para evitar problemas em testes
            // $table->foreign('user_id')->references('id')->on('users');
        });
        
        // Outras tabelas que o Laravel pode precisar
        Schema::create('password_reset_tokens', function ($table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
        
        Schema::create('migrations', function ($table) {
            $table->id();
            $table->string('migration');
            $table->integer('batch');
        });
    }
}