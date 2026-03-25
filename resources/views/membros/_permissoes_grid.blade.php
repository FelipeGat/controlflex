@php
$modulos = [
    'despesas'      => ['label' => 'Despesas',      'icon' => 'fa-arrow-trend-down'],
    'receitas'      => ['label' => 'Receitas',       'icon' => 'fa-arrow-trend-up'],
    'investimentos' => ['label' => 'Investimentos',  'icon' => 'fa-seedling'],
    'bancos'        => ['label' => 'Contas Bancárias','icon' => 'fa-building-columns'],
    'categorias'    => ['label' => 'Categorias',     'icon' => 'fa-tags'],
    'fornecedores'  => ['label' => 'Fornecedores',   'icon' => 'fa-store'],
    'familiares'    => ['label' => 'Familiares',      'icon' => 'fa-users'],
];
$acoes = ['ver' => 'Ver', 'criar' => 'Criar', 'editar' => 'Editar', 'excluir' => 'Excluir'];
@endphp

<div style="border:1px solid var(--color-border);border-radius:8px;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="background:#fafafa;">
                <th style="padding:8px 12px;text-align:left;font-size:11px;font-weight:700;color:var(--color-text-subtle);text-transform:uppercase;border-bottom:1px solid var(--color-border);">Módulo</th>
                @foreach($acoes as $acao => $label)
                <th style="padding:8px 12px;text-align:center;font-size:11px;font-weight:700;color:var(--color-text-subtle);text-transform:uppercase;border-bottom:1px solid var(--color-border);">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($modulos as $modulo => $info)
            <tr style="border-bottom:1px solid #f8fafc;">
                <td style="padding:9px 12px;font-weight:500;color:var(--color-text);">
                    <i class="fa-solid {{ $info['icon'] }}" style="width:16px;color:var(--color-text-subtle);margin-right:6px;"></i>
                    {{ $info['label'] }}
                </td>
                @foreach($acoes as $acao => $label)
                <td style="padding:9px 12px;text-align:center;">
                    <input type="checkbox"
                        name="perm_{{ $modulo }}_{{ $acao }}"
                        value="1"
                        {{ isset($permissoes[$modulo][$acao]) && $permissoes[$modulo][$acao] ? 'checked' : '' }}>
                </td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
