<?php

use App\Models\Categoria;
use App\Models\User;
use App\Support\Dinero;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (DB::connection()->getDriverName() !== 'mysql') {
    throw new RuntimeException('La conexión de trabajo no es MySQL.');
}
// Consulta sin tablas de la aplicación: valores sintéticos y cero escrituras.
$fixture = "(SELECT 1 AS user_id, DATE('2026-01-10') AS fecha, CAST('0.10' AS DECIMAL(12,2)) AS monto
    UNION ALL SELECT 1, DATE('2026-05-10'), CAST('0.20' AS DECIMAL(12,2))
    UNION ALL SELECT 1, DATE('2026-07-10'), CAST('900.00' AS DECIMAL(12,2))
    UNION ALL SELECT 2, DATE('2026-05-10'), CAST('500.00' AS DECIMAL(12,2))) AS movimientos";
$row = DB::selectOne("SELECT CAST(COALESCE(SUM(CASE WHEN MONTH(fecha) = ? THEN monto ELSE 0 END),0) AS CHAR) AS mes,
    CAST(COALESCE(SUM(monto),0) AS CHAR) AS acumulado FROM $fixture WHERE user_id = ? AND YEAR(fecha) = ? AND MONTH(fecha) <= ?", [5, 1, 2026, 5]);
if (Dinero::decimal($row->mes) !== '0.20' || Dinero::decimal($row->acumulado) !== '0.30') {
    throw new RuntimeException('Error en el acumulado o el aislamiento.');
}
$empty = DB::selectOne("SELECT CAST(COALESCE(SUM(monto),0) AS CHAR) AS total FROM $fixture WHERE user_id=1 AND MONTH(fecha)=6");
if (Dinero::decimal($empty->total) !== '0.00') {
    throw new RuntimeException('Error en el mes vacío.');
}
echo "MySQL: precisión decimal, acumulado hasta mayo, aislamiento y junio vacío correctos. Sin escrituras.\n";

// CRUD real en MySQL, dentro de una transacción que siempre se revierte.
DB::beginTransaction();
try {
    $user = User::factory()->create();
    $token = $user->createToken('verificacion-temporal')->plainTextToken;
    $incomeCategory = Categoria::create(['user_id' => $user->id, 'nombre' => 'Verificación ingreso', 'tipo' => 'ingreso']);
    $expenseCategory = Categoria::create(['user_id' => $user->id, 'nombre' => 'Verificación egreso', 'tipo' => 'egreso']);
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $call = function (string $method, string $uri, array $payload, int $expected) use ($kernel, $token) {
        Auth::forgetGuards();
        $request = Request::create($uri, $method, [], [], [], [
            'HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ], json_encode($payload, JSON_THROW_ON_ERROR));
        $response = $kernel->handle($request);
        if ($response->getStatusCode() !== $expected) {
            throw new RuntimeException("Fallo en $method $uri: HTTP ".$response->getStatusCode());
        }

        return json_decode($response->getContent(), true);
    };
    foreach (['ingresos' => $incomeCategory, 'egresos' => $expenseCategory] as $resource => $category) {
        $payload = ['fecha' => '2026-05-10', 'monto' => '10.25', 'categoria_id' => $category->id,
            $resource === 'ingresos' ? 'fuente' : 'descripcion' => 'Registro temporal de verificación'];
        $created = $call('POST', '/api/'.$resource, $payload, 201);
        $updated = $call('PUT', '/api/'.$resource.'/'.$created['id'], [...$payload, 'monto' => '20.30'], 200);
        if ($updated['monto'] !== '20.30') {
            throw new RuntimeException('El monto editado no coincide.');
        }
        $list = $call('GET', '/api/'.$resource.'?anio=2026&mes=5', [], 200);
        if (count($list) !== 1) {
            throw new RuntimeException('El listado no está aislado.');
        }
        $call('DELETE', '/api/'.$resource.'/'.$created['id'], [], 204);
        if ($call('GET', '/api/'.$resource, [], 200) !== []) {
            throw new RuntimeException('No se eliminó el registro temporal.');
        }
    }
    echo "MySQL: API autenticada, creación, edición, filtros y eliminación de ingresos y egresos correctos.\n";
} finally {
    DB::rollBack();
    echo "Transacción de prueba revertida; no se conservaron usuarios, tokens ni movimientos temporales.\n";
}
