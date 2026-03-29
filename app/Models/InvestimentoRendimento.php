<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class InvestimentoRendimento extends Model
{
    use BelongsToTenant;

    protected $table = 'investimento_rendimentos';

    protected $fillable = [
        'investimento_id', 'tenant_id', 'data', 'valor_atual', 'observacoes',
    ];

    protected $casts = [
        'data'        => 'date',
        'valor_atual' => 'decimal:2',
    ];

    public function investimento()
    {
        return $this->belongsTo(Investimento::class);
    }
}
