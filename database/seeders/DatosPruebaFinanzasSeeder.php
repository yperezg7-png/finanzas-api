<?php

namespace Database\Seeders;

use App\Models\Egreso;
use App\Models\Ingreso;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatosPruebaFinanzasSeeder extends Seeder
{
    /**
     * Seed test users and financial movements for dashboard validation.
     */
    public function run(): void
    {
        $this->call(CategoriaSeeder::class);

        $usuarios = [
            [
                'name' => 'Andrea Lopez',
                'email' => 'andrea.estudiante@example.com',
                'password' => 'password123',
                'ingresos' => [1 => 2200, 2 => 2350, 3 => 2100, 4 => 2450, 5 => 2300, 7 => 2600],
            ],
            [
                'name' => 'Carlos Martinez',
                'email' => 'carlos.estudiante@example.com',
                'password' => 'password123',
                'ingresos' => [1 => 1800, 2 => 1950, 3 => 2050, 4 => 1900, 5 => 2150, 7 => 2250],
            ],
        ];

        foreach ($usuarios as $datosUsuario) {
            $usuario = User::query()->updateOrCreate(
                ['email' => $datosUsuario['email']],
                [
                    'name' => $datosUsuario['name'],
                    'password' => Hash::make($datosUsuario['password']),
                    'email_verified_at' => now(),
                ],
            );

            Ingreso::query()->where('user_id', $usuario->id)->delete();
            Egreso::query()->where('user_id', $usuario->id)->delete();

            $this->crearIngresos($usuario, $datosUsuario['ingresos']);
            $this->crearEgresos($usuario);
        }
    }

    /**
     * @param  array<int, int>  $montosPorMes
     */
    private function crearIngresos(User $usuario, array $montosPorMes): void
    {
        $categorias = DB::table('categorias')
            ->whereNull('user_id')
            ->where('tipo', 'ingreso')
            ->pluck('id', 'nombre');

        $fuentes = [
            1 => ['Empleo', 'Apoyo mensual de trabajo parcial'],
            2 => ['Freelance / Proyecto', 'Proyecto freelance universitario'],
            3 => ['Empleo', 'Pago por tutorias'],
            4 => ['Negocio Propio', 'Ventas de emprendimiento'],
            5 => ['Bono / Extra', 'Ingreso extra de temporada'],
            7 => ['Freelance / Proyecto', 'Proyecto de vacaciones'],
        ];

        foreach ($montosPorMes as $mes => $monto) {
            [$categoria, $fuente] = $fuentes[$mes];

            Ingreso::factory()->create([
                'user_id' => $usuario->id,
                'categoria_id' => $categorias[$categoria],
                'fecha' => sprintf('2026-%02d-05', $mes),
                'fuente' => $fuente,
                'monto' => $monto,
            ]);
        }
    }

    private function crearEgresos(User $usuario): void
    {
        $egresosPorMes = [
            1 => [
                ['Vivienda', 'Alquiler', 'Habitacion compartida', 900],
                ['Vivienda', 'Internet', 'Internet residencial', 125],
                ['Alimentación', 'Supermercado', 'Compras de despensa', 420],
                ['Alimentación', 'Almuerzo', 'Almuerzos en campus', 180],
                ['Transporte', 'Bus', 'Pasajes de bus', 110],
                ['Educación', 'Libros', 'Materiales de clase', 240],
                ['Salud', 'Medicamentos', 'Medicamentos basicos', 75],
                ['Ocio / Entretenimiento', 'Suscripciones', 'Streaming mensual', 65],
            ],
            2 => [
                ['Vivienda', 'Alquiler', 'Habitacion compartida', 900],
                ['Vivienda', 'Luz', 'Recibo de energia', 95],
                ['Alimentación', 'Supermercado', 'Compras de despensa', 390],
                ['Transporte', 'Bus', 'Pasajes de bus', 120],
                ['Transporte', 'Taxi / Uber', 'Viajes nocturnos', 85],
                ['Educación', 'Cursos', 'Curso corto en linea', 180],
                ['Deporte', 'Gimnasio', 'Mensualidad gimnasio', 150],
            ],
            3 => [
                ['Vivienda', 'Alquiler', 'Habitacion compartida', 900],
                ['Vivienda', 'Agua', 'Recibo de agua', 70],
                ['Alimentación', 'Restaurante', 'Comidas fuera', 210],
                ['Alimentación', 'Supermercado', 'Compras de despensa', 430],
                ['Transporte', 'Bus', 'Pasajes de bus', 125],
                ['Educación', 'Universidad', 'Pago universitario', 650],
                ['Salud', 'Consulta', 'Consulta medica', 200],
                ['Imprevistos', 'Reparaciones', 'Reparacion de mochila', 90],
                ['Ocio / Entretenimiento', 'Cine', 'Salida al cine', 80],
            ],
            4 => [
                ['Vivienda', 'Alquiler', 'Habitacion compartida', 900],
                ['Vivienda', 'Internet', 'Internet residencial', 125],
                ['Alimentación', 'Supermercado', 'Compras de despensa', 405],
                ['Alimentación', 'Almuerzo', 'Almuerzos en campus', 190],
                ['Transporte', 'Gasolina', 'Aporte de gasolina', 160],
                ['Transporte', 'Parqueo', 'Parqueo universitario', 60],
                ['Educación', 'Libros', 'Fotocopias y libros', 210],
                ['Otro Egreso', null, 'Gasto menor sin categoria', 55],
            ],
            5 => [
                ['Vivienda', 'Alquiler', 'Habitacion compartida', 900],
                ['Vivienda', 'Luz', 'Recibo de energia', 105],
                ['Alimentación', 'Supermercado', 'Compras de despensa', 450],
                ['Transporte', 'Bus', 'Pasajes de bus', 115],
                ['Educación', 'Cursos', 'Taller universitario', 220],
                ['Salud', 'Laboratorio', 'Examen de laboratorio', 180],
                ['Deporte', 'Equipo deportivo', 'Accesorio deportivo', 130],
                ['Imprevistos', 'Emergencias', 'Emergencia menor', 250],
                ['Ocio / Entretenimiento', 'Salidas', 'Salida con amigos', 120],
                ['Otro Egreso', null, 'Papeleria variada', 45],
            ],
            7 => [
                ['Vivienda', 'Alquiler', 'Habitacion compartida', 900],
                ['Vivienda', 'Agua', 'Recibo de agua', 75],
                ['Vivienda', 'Internet', 'Internet residencial', 125],
                ['Alimentación', 'Supermercado', 'Compras de despensa', 460],
                ['Alimentación', 'Restaurante', 'Comidas fuera', 170],
                ['Transporte', 'Taxi / Uber', 'Viajes por practicas', 140],
                ['Educación', 'Universidad', 'Pago universitario', 650],
                ['Salud', 'Medicamentos', 'Medicamentos basicos', 90],
                ['Ocio / Entretenimiento', 'Suscripciones', 'Streaming mensual', 65],
            ],
        ];

        foreach ($egresosPorMes as $mes => $egresos) {
            foreach ($egresos as $dia => [$categoria, $subcategoria, $descripcion, $monto]) {
                Egreso::factory()->create([
                    'user_id' => $usuario->id,
                    'categoria_id' => $this->categoriaId($categoria),
                    'subcategoria_id' => $subcategoria === null
                        ? null
                        : $this->subcategoriaId($categoria, $subcategoria),
                    'fecha' => sprintf('2026-%02d-%02d', $mes, $dia + 3),
                    'descripcion' => $descripcion,
                    'monto' => $monto,
                ]);
            }
        }
    }

    private function categoriaId(string $nombre): int
    {
        return DB::table('categorias')
            ->whereNull('user_id')
            ->where('nombre', $nombre)
            ->value('id');
    }

    private function subcategoriaId(string $categoria, string $subcategoria): int
    {
        return DB::table('subcategorias')
            ->join('categorias', 'categorias.id', '=', 'subcategorias.categoria_id')
            ->whereNull('categorias.user_id')
            ->where('categorias.nombre', $categoria)
            ->where('subcategorias.nombre', $subcategoria)
            ->value('subcategorias.id');
    }
}
