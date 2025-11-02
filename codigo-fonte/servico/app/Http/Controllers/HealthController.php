<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class HealthController extends Controller
{
    /**
     * Verifica o status geral da aplicação
     */
    public function index()
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'app' => [
                'name' => config('app.name'),
                'env' => config('app.env'),
                'debug' => config('app.debug'),
            ],
        ]);
    }

    /**
     * Verifica a conexão com o banco de dados
     */
    public function database()
    {
        $startTime = microtime(true);

        try {
            // Tentar conectar e executar uma query simples
            DB::connection()->getPdo();
            $databaseName = DB::connection()->getDatabaseName();

            // Executar query de teste
            $result = DB::selectOne('SELECT 1 as test');

            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2); // em milissegundos

            return response()->json([
                'status' => 'connected',
                'connection' => 'success',
                'driver' => config('database.default'),
                'database' => $databaseName,
                'host' => config('database.connections.sqlsrv.host'),
                'port' => config('database.connections.sqlsrv.port'),
                'response_time_ms' => $duration,
                'test_query' => 'SELECT 1',
                'test_result' => $result->test ?? null,
                'timestamp' => now()->toISOString(),
            ], 200);

        } catch (Exception $e) {
            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);

            return response()->json([
                'status' => 'error',
                'connection' => 'failed',
                'driver' => config('database.default'),
                'database' => config('database.connections.sqlsrv.database'),
                'host' => config('database.connections.sqlsrv.host'),
                'port' => config('database.connections.sqlsrv.port'),
                'response_time_ms' => $duration,
                'error' => [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                ],
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * Verifica conexão e retorna informações das tabelas
     */
    public function databaseTables()
    {
        try {
            // Conectar ao banco
            DB::connection()->getPdo();

            // Listar tabelas (SQL Server)
            $tables = DB::select("
                SELECT TABLE_NAME
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_TYPE = 'BASE TABLE'
                AND TABLE_CATALOG = ?
                ORDER BY TABLE_NAME
            ", [DB::connection()->getDatabaseName()]);

            $tableNames = array_map(function($table) {
                return $table->TABLE_NAME;
            }, $tables);

            // Contar registros em cada tabela
            $tableStats = [];
            foreach ($tableNames as $tableName) {
                try {
                    $count = DB::table($tableName)->count();
                    $tableStats[$tableName] = $count;
                } catch (Exception $e) {
                    $tableStats[$tableName] = 'error: ' . $e->getMessage();
                }
            }

            return response()->json([
                'status' => 'connected',
                'connection' => 'success',
                'database' => DB::connection()->getDatabaseName(),
                'tables_count' => count($tableNames),
                'tables' => $tableNames,
                'table_stats' => $tableStats,
                'timestamp' => now()->toISOString(),
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'connection' => 'failed',
                'error' => [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                ],
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }
}
