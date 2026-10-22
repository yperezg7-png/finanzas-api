<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Egreso;
use App\Models\Subcategoria;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Egreso>
 */
class EgresoFactory extends Factory
{
    protected $model = Egreso::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categoriaId = Categoria::query()
            ->whereNull('user_id')
            ->where('tipo', 'egreso')
            ->inRandomOrder()
            ->value('id');

        return [
            'user_id' => User::factory(),
            'categoria_id' => $categoriaId,
            'subcategoria_id' => Subcategoria::query()
                ->where('categoria_id', $categoriaId)
                ->inRandomOrder()
                ->value('id'),
            'fecha' => fake()->date(),
            'descripcion' => fake()->randomElement([
                'Compra diaria',
                'Pago de servicio',
                'Gasto universitario',
                'Transporte',
                'Consumo personal',
            ]),
            'monto' => bcdiv((string) fake()->numberBetween(1000, 90000), '100', 2),
            'notas' => null,
        ];
    }
}
