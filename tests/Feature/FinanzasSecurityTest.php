<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Egreso;
use App\Models\Ingreso;
use App\Models\Subcategoria;
use App\Models\User;
use App\Support\Dinero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FinanzasSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // El SQL de producción usa MONTH de MySQL; equivalente solo para SQLite en memoria.
        DB::connection()->getPdo()->sqliteCreateFunction('MONTH', fn ($date) => (int) substr($date, 5, 2), 1);
    }

    private function category(?User $user = null, string $tipo = 'egreso', string $name = 'Prueba'): Categoria
    {
        return Categoria::create(['user_id' => $user?->id, 'tipo' => $tipo, 'nombre' => $name]);
    }

    private function payload(Categoria $category): array
    {
        return ['categoria_id' => $category->id, 'fecha' => '2026-01-10', 'descripcion' => 'Prueba', 'monto' => '10.25'];
    }

    public function test_usuarios_no_pueden_leer_modificar_ni_borrar_movimientos_ajenos(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $category = $this->category();
        $expense = Egreso::create([...$this->payload($category), 'user_id' => $owner->id]);
        $income = Ingreso::create(['user_id' => $owner->id, 'categoria_id' => $this->category(null, 'ingreso')->id, 'fecha' => '2026-01-10', 'fuente' => 'Prueba', 'monto' => '10.25']);
        Sanctum::actingAs($other);
        foreach (['egresos' => $expense, 'ingresos' => $income] as $resource => $model) {
            $this->getJson("/api/$resource")->assertOk()->assertExactJson([]);
            $this->getJson("/api/$resource/{$model->id}")->assertNotFound();
            $this->putJson("/api/$resource/{$model->id}", ['monto' => 'incorrecto'])->assertNotFound();
            $this->deleteJson("/api/$resource/{$model->id}")->assertNotFound();
        }
        $this->assertDatabaseHas('egresos', ['id' => $expense->id, 'user_id' => $owner->id]);
    }

    public function test_catalogo_incluye_sistema_y_propias_y_rechaza_ajenas(): void
    {
        $user = User::factory()->create();
        $system = $this->category();
        $own = $this->category($user);
        $foreign = $this->category(User::factory()->create());
        Sanctum::actingAs($user);
        $this->getJson('/api/categorias?tipo=egreso')->assertOk()->assertJsonCount(2);
        foreach ([$system, $foreign] as $category) {
            $this->putJson("/api/categorias/{$category->id}", ['nombre' => 'Cambio'])->assertNotFound();
            $this->deleteJson("/api/categorias/{$category->id}")->assertNotFound();
        }
        $this->postJson('/api/egresos', $this->payload($foreign))->assertUnprocessable()->assertJsonValidationErrors('categoria_id');
        $this->postJson('/api/egresos', $this->payload($own))->assertCreated();
    }

    public function test_validacion_de_filtros_y_limites_monetarios(): void
    {
        Sanctum::actingAs(User::factory()->create());
        foreach (['egresos', 'ingresos'] as $resource) {
            $this->getJson("/api/$resource?anio=texto&mes=13")->assertUnprocessable()->assertJsonValidationErrors(['anio', 'mes']);
            $category = $this->category(null, $resource === 'egresos' ? 'egreso' : 'ingreso');
            foreach (['1.001', '10000000000.00', '0', '-1'] as $amount) {
                $this->postJson("/api/$resource", [...$this->payload($category), 'fuente' => 'Ingreso', 'monto' => $amount])->assertUnprocessable()->assertJsonValidationErrors('monto');
            }
            $this->postJson("/api/$resource", [...$this->payload($category), 'fuente' => 'Ingreso', 'monto' => '9999999999.99'])->assertCreated();
        }
        $this->getJson('/api/dashboard/resumen?anio=2026&mes=0')->assertUnprocessable();
    }

    public function test_edicion_parcial_mantiene_coherencia_de_subcategoria(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $first = $this->category();
        $second = $this->category(null, 'egreso', 'Otra');
        $sub = Subcategoria::create(['categoria_id' => $first->id, 'nombre' => 'Sub']);
        $expense = Egreso::create([...$this->payload($first), 'user_id' => $user->id, 'subcategoria_id' => $sub->id]);
        $this->patchJson("/api/egresos/{$expense->id}", ['monto' => '12.00'])->assertOk()->assertJsonPath('subcategoria_id', $sub->id);
        $this->patchJson("/api/egresos/{$expense->id}", ['categoria_id' => $second->id, 'subcategoria_id' => $sub->id])->assertUnprocessable()->assertJsonValidationErrors('subcategoria_id');
        $this->patchJson("/api/egresos/{$expense->id}", ['categoria_id' => $second->id])->assertOk()->assertJsonPath('subcategoria_id', null);
    }

    public function test_categorias_duplicadas_devuelven_422_y_no_error_sql(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $one = $this->category($user, 'egreso', 'Uno');
        $two = $this->category($user, 'egreso', 'Dos');
        $this->postJson('/api/categorias', ['nombre' => 'Uno', 'tipo' => 'egreso'])->assertUnprocessable()->assertJsonValidationErrors('nombre');
        $this->patchJson("/api/categorias/{$two->id}", ['nombre' => 'Uno'])->assertUnprocessable();
        $this->patchJson("/api/categorias/{$one->id}", ['nombre' => 'Uno'])->assertOk();
    }

    public function test_resumen_decimal_excluye_meses_posteriores_y_otros_usuarios(): void
    {
        $user = User::factory()->create();
        $category = $this->category(null, 'ingreso');
        foreach ([[1, '0.10'], [5, '0.20'], [7, '900.00']] as [$month, $amount]) {
            Ingreso::create(['user_id' => $user->id, 'categoria_id' => $category->id, 'fuente' => 'Prueba', 'fecha' => sprintf('2026-%02d-10', $month), 'monto' => $amount]);
        }
        Ingreso::create(['user_id' => User::factory()->create()->id, 'categoria_id' => $category->id, 'fuente' => 'Ajeno', 'fecha' => '2026-05-10', 'monto' => '500.00']);
        Sanctum::actingAs($user);
        $this->getJson('/api/dashboard/resumen?anio=2026&mes=5')->assertOk()->assertJsonPath('ingresos_mes', '0.20')->assertJsonPath('ingresos_acumulados', '0.30')->assertJsonPath('balance_acumulado', '0.30');
        $this->getJson('/api/dashboard/resumen?anio=2026&mes=6')->assertOk()->assertJsonPath('ingresos_mes', '0.00')->assertJsonPath('porcentaje_gastado', '0.00');
        $annual = $this->getJson('/api/dashboard/resumen-anual?anio=2026')->assertOk()->assertJsonCount(12);
        $annual->assertJsonPath('5.ingresos', '0.00')->assertJsonPath('6.ingresos', '900.00');
    }

    public function test_agrupacion_usa_orden_numerico_y_aislamiento(): void
    {
        $user = User::factory()->create();
        foreach (['9.00', '100.00'] as $index => $amount) {
            Egreso::create([...$this->payload($this->category(null, 'egreso', "Grupo $index")), 'monto' => $amount, 'user_id' => $user->id]);
        }
        Egreso::create([...$this->payload($this->category()), 'monto' => '999.00', 'user_id' => User::factory()->create()->id]);
        Sanctum::actingAs($user);
        $this->getJson('/api/dashboard/egresos-por-categoria?anio=2026&mes=1')->assertOk()->assertJsonCount(2)->assertJsonPath('0.total', '100.00')->assertJsonPath('1.total', '9.00');
    }

    public function test_login_limita_intentos_y_no_expone_contrasena(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        $response = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password123'])->assertOk();
        $response->assertJsonMissingPath('user.password')->assertJsonMissingPath('user.remember_token');
        for ($i = 0; $i < 9; $i++) {
            $this->postJson('/api/login', ['email' => $user->email, 'password' => 'incorrecta'])->assertUnprocessable();
        }
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'incorrecta'])->assertStatus(429);
    }

    public function test_logout_revoca_solo_el_token_actual(): void
    {
        $user = User::factory()->create();
        $first = $user->createToken('primero');
        $second = $user->createToken('segundo');
        $this->withToken($first->plainTextToken)->postJson('/api/logout')->assertOk();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $first->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $second->accessToken->id]);
    }

    public function test_entradas_malformadas_devuelven_422(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $category = $this->category();
        $this->postJson('/api/egresos', [...$this->payload($category), 'categoria_id' => ['no'], 'subcategoria_id' => 1])->assertUnprocessable()->assertJsonValidationErrors('categoria_id');
        $this->postJson('/api/categorias', ['nombre' => ['no'], 'tipo' => ['no']])->assertUnprocessable();
        $this->getJson('/api/egresos?mes[]=1')->assertUnprocessable();
    }

    public function test_bcmath_conserva_precision_y_redondea_porcentaje(): void
    {
        $this->assertSame('0.10', Dinero::resta('0.30', '0.20'));
        $this->assertSame('-0.10', Dinero::resta('0.20', '0.30'));
        $this->assertSame('66.67', Dinero::porcentaje('2.00', '3.00'));
        $this->assertSame('0.00', Dinero::porcentaje('100.00', '0.00'));
        $this->assertSame('9999999999.98', Dinero::resta('9999999999.99', '0.01'));
    }
}
