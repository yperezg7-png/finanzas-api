<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriaSeeder extends Seeder
{
    /**
     * Seed the system categories and subcategories.
     */
    public function run(): void
    {
        $ingresos = [
            'Empleo',
            'Freelance / Proyecto',
            'Negocio Propio',
            'Inversión / Dividendos',
            'Bono / Extra',
            'Otro Ingreso',
        ];

        $egresos = [
            'Vivienda' => ['Alquiler', 'Agua', 'Luz', 'Internet'],
            'Educación' => ['Universidad', 'Cursos', 'Libros'],
            'Alimentación' => ['Supermercado', 'Restaurante', 'Almuerzo'],
            'Transporte' => ['Gasolina', 'Bus', 'Taxi / Uber', 'Parqueo'],
            'Salud' => ['Consulta', 'Medicamentos', 'Laboratorio'],
            'Ocio / Entretenimiento' => ['Suscripciones', 'Cine', 'Salidas'],
            'Deporte' => ['Gimnasio', 'Equipo deportivo'],
            'Imprevistos' => ['Emergencias', 'Reparaciones'],
            'Otro Egreso' => [],
        ];

        foreach ($ingresos as $nombre) {
            $this->categoriaSistema($nombre, 'ingreso');
        }

        foreach ($egresos as $nombre => $subcategorias) {
            $categoriaId = $this->categoriaSistema($nombre, 'egreso');

            foreach ($subcategorias as $subcategoria) {
                $this->subcategoria($categoriaId, $subcategoria);
            }
        }
    }

    private function categoriaSistema(string $nombre, string $tipo): int
    {
        $categoria = DB::table('categorias')
            ->whereNull('user_id')
            ->where('nombre', $nombre)
            ->where('tipo', $tipo)
            ->first();

        if ($categoria !== null) {
            return $categoria->id;
        }

        return DB::table('categorias')->insertGetId([
            'user_id' => null,
            'nombre' => $nombre,
            'tipo' => $tipo,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function subcategoria(int $categoriaId, string $nombre): void
    {
        $existe = DB::table('subcategorias')
            ->where('categoria_id', $categoriaId)
            ->where('nombre', $nombre)
            ->exists();

        if ($existe) {
            return;
        }

        DB::table('subcategorias')->insert([
            'categoria_id' => $categoriaId,
            'nombre' => $nombre,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
