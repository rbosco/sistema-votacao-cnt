<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuracao extends Model
{
    use HasFactory;

    protected $table = 'configuracoes';

    protected $fillable = [
        'chave',
        'valor',
    ];

    /**
     * Obter valor de uma configuração pela chave
     */
    public static function obter($chave, $padrao = null)
    {
        $config = self::where('chave', $chave)->first();
        return $config ? $config->valor : $padrao;
    }

    /**
     * Definir valor de uma configuração
     */
    public static function definir($chave, $valor)
    {
        return self::updateOrCreate(
            ['chave' => $chave],
            ['valor' => $valor]
        );
    }

    /**
     * Obter todas as configurações como array chave => valor
     */
    public static function obterTodas()
    {
        return self::all()->pluck('valor', 'chave')->toArray();
    }

    /**
     * Verificar se o temporizador está ativo e não expirado
     */
    public static function temporizadorAtivo()
    {
        $ativo = self::obter('temporizador_ativo', '0');

        if ($ativo !== '1') {
            return false;
        }

        $inicio = self::obter('temporizador_inicio');
        $duracao = (int) self::obter('temporizador_duracao_minutos', 30);

        if (!$inicio) {
            return false;
        }

        $inicioTime = strtotime($inicio);
        $fimTime = $inicioTime + ($duracao * 60);
        $agoraTime = time();

        return $agoraTime <= $fimTime;
    }

    /**
     * Obter tempo restante do temporizador em segundos
     */
    public static function tempoRestante()
    {
        if (!self::temporizadorAtivo()) {
            return 0;
        }

        $inicio = self::obter('temporizador_inicio');
        $duracao = (int) self::obter('temporizador_duracao_minutos', 30);

        $inicioTime = strtotime($inicio);
        $fimTime = $inicioTime + ($duracao * 60);
        $agoraTime = time();

        return max(0, $fimTime - $agoraTime);
    }
}
