<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CupomIndicacao extends Model
{
    protected $table = 'cupons_indicacao';

    protected $fillable = [
        'tenant_id',
        'codigo',
        'desconto_percentual',
        'creditos_disponiveis',
        'creditos_utilizados',
        'ativo',
    ];

    protected $casts = [
        'desconto_percentual'  => 'decimal:2',
        'creditos_disponiveis' => 'integer',
        'creditos_utilizados'  => 'integer',
        'ativo'                => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function indicacoes()
    {
        return $this->hasMany(Indicacao::class, 'cupom_id');
    }

    public function getCreditosPendentesAttribute(): int
    {
        return $this->creditos_disponiveis - $this->creditos_utilizados;
    }

    public static function gerarCodigo(string $nome): string
    {
        $codigo = mb_strtoupper(
            preg_replace('/[^A-Za-z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', explode(' ', trim($nome))[0]))
        );

        if (static::where('codigo', $codigo)->exists()) {
            $codigo .= rand(10, 99);
        }

        return $codigo;
    }
}
