<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Ingreso;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ingreso>
 */
class IngresoFactory extends Factory
{
    protected $model = Ingreso::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'categoria_id' => Categoria::query()
                ->whereNull('user_id')
                ->where('tipo', 'ingreso')
                ->inRandomOrder()
                ->value('id'),
            'fecha' => fake()->date(),
            'fuente' => fake()->randomElement([
                'Pago mensual',
                'Proyecto entregado',
                'Venta ocasional',
                'Bono recibido',
            ]),
            'monto' => fake()->randomFloat(2, 800, 3500),
            'notas' => null,
        ];
    }
}
