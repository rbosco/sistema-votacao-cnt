<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Para SQL Server, precisamos remover a constraint CHECK antiga
        // e adicionar uma nova com os valores corretos

        // Passo 1: Encontrar e remover a constraint CHECK existente
        $constraintName = DB::select("
            SELECT CONSTRAINT_NAME
            FROM INFORMATION_SCHEMA.CONSTRAINT_COLUMN_USAGE
            WHERE TABLE_NAME = 'propostas'
            AND COLUMN_NAME = 'status'
        ");

        if (!empty($constraintName)) {
            $name = $constraintName[0]->CONSTRAINT_NAME;
            DB::statement("ALTER TABLE propostas DROP CONSTRAINT [{$name}]");
        }

        // Passo 2: Remover o default constraint se existir
        $defaultConstraint = DB::select("
            SELECT dc.name
            FROM sys.default_constraints dc
            JOIN sys.columns c ON dc.parent_object_id = c.object_id AND dc.parent_column_id = c.column_id
            WHERE OBJECT_NAME(dc.parent_object_id) = 'propostas'
            AND c.name = 'status'
        ");

        if (!empty($defaultConstraint)) {
            $name = $defaultConstraint[0]->name;
            DB::statement("ALTER TABLE propostas DROP CONSTRAINT [{$name}]");
        }

        // Passo 3: Modificar a coluna para aceitar os novos valores
        DB::statement("ALTER TABLE propostas ALTER COLUMN status NVARCHAR(20) NOT NULL");

        // Passo 4: Adicionar novo default
        DB::statement("ALTER TABLE propostas ADD DEFAULT 'nao_iniciada' FOR status");

        // Passo 5: Adicionar nova constraint CHECK com os 3 valores
        DB::statement("
            ALTER TABLE propostas
            ADD CONSTRAINT CK_propostas_status
            CHECK (status IN ('nao_iniciada', 'em_votacao', 'encerrada'))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover a constraint nova
        DB::statement("ALTER TABLE propostas DROP CONSTRAINT IF EXISTS CK_propostas_status");

        // Remover o default
        $defaultConstraint = DB::select("
            SELECT dc.name
            FROM sys.default_constraints dc
            JOIN sys.columns c ON dc.parent_object_id = c.object_id AND dc.parent_column_id = c.column_id
            WHERE OBJECT_NAME(dc.parent_object_id) = 'propostas'
            AND c.name = 'status'
        ");

        if (!empty($defaultConstraint)) {
            $name = $defaultConstraint[0]->name;
            DB::statement("ALTER TABLE propostas DROP CONSTRAINT [{$name}]");
        }

        // Adicionar o default antigo
        DB::statement("ALTER TABLE propostas ADD DEFAULT 'em_votacao' FOR status");

        // Adicionar constraint CHECK antiga
        DB::statement("
            ALTER TABLE propostas
            ADD CHECK (status IN ('em_votacao', 'encerrada'))
        ");
    }
};
