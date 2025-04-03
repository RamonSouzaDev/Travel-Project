<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TravelRequest>
 */
class TravelRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'destination' => $this->faker->city(),
            'departure_date' => $this->faker->dateTimeBetween('+1 week', '+2 weeks'),
            'return_date' => $this->faker->dateTimeBetween('+3 weeks', '+4 weeks'),
            'status' => $this->faker->randomElement(['solicitado', 'aprovado', 'cancelado']),
            'reason_for_cancellation' => function (array $attributes) {
                return $attributes['status'] === 'cancelado' 
                    ? $this->faker->sentence() 
                    : null;
            },
        ];
    }
    
    /**
     * Define o estado do pedido como solicitado.
     */
    public function solicitado()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'solicitado',
            'reason_for_cancellation' => null,
        ]);
    }
    
    /**
     * Define o estado do pedido como aprovado.
     */
    public function aprovado()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'aprovado',
            'reason_for_cancellation' => null,
        ]);
    }
    
    /**
     * Define o estado do pedido como cancelado.
     */
    public function cancelado()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelado',
            'reason_for_cancellation' => $this->faker->sentence(),
        ]);
    }
}
