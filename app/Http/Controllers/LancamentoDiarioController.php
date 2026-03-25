<?php

namespace App\Http\Controllers;

use App\Models\Banco;
use App\Models\Categoria;
use App\Models\Despesa;
use App\Models\Familiar;
use App\Models\Fornecedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class LancamentoDiarioController extends Controller
{
    public function index()
    {
        $categorias   = Categoria::where('tipo', 'DESPESA')->orderBy('nome')->get();
        $familiares   = Familiar::orderBy('nome')->get();
        $bancos       = Banco::orderBy('nome')->get();
        $fornecedores = Fornecedor::orderBy('nome')->get();

        $lancamentosHoje = Despesa::with(['categoria', 'fornecedor', 'banco'])
            ->whereDate('data_compra', today())
            ->orderByDesc('id')
            ->get();

        // Familiar padrão do usuário logado (para pré-selecionar "Quem Comprou")
        $meuFamiliarId = Auth::user()->familiar_id;

        return view('lancamentos-diarios.index', compact(
            'categorias', 'familiares', 'bancos', 'fornecedores',
            'lancamentosHoje', 'meuFamiliarId'
        ));
    }

    /**
     * Processa o cupom fiscal enviado pelo cliente.
     * Aceita dois modos:
     *   - qr_code: string decodificada de um QR NFC-e/SAT (parse local, sem IA)
     *   - imagem:  base64 de foto do cupom → OCR via Claude Vision API
     */
    public function escanear(Request $request)
    {
        // ── Modo 1: QR Code (NFC-e / SAT) ─────────────────────────────────
        if ($request->filled('qr_code')) {
            return response()->json($this->parseQRCode(trim($request->input('qr_code'))));
        }

        // ── Modo 2: Imagem (ECF / NFC-e sem QR) → Claude Vision ───────────
        if ($request->filled('imagem')) {
            $apiKey = config('services.anthropic.key', env('ANTHROPIC_API_KEY'));

            if (empty($apiKey)) {
                return response()->json([
                    'erro' => 'Chave da API Anthropic não configurada (ANTHROPIC_API_KEY no .env).',
                ], 422);
            }

            return response()->json($this->processarImagemComClaude(
                $request->input('imagem'),
                $request->input('mime', 'image/jpeg'),
                $apiKey
            ));
        }

        return response()->json(['erro' => 'Nenhum dado enviado.'], 422);
    }

    // ────────────────────────────────────────────────────────────────────────
    //  MODO 2 — OCR via Claude Vision
    // ────────────────────────────────────────────────────────────────────────

    private function processarImagemComClaude(string $base64, string $mime, string $apiKey): array
    {
        $prompt = <<<'PROMPT'
Você é um especialista em leitura de cupons fiscais brasileiros (ECF, NFC-e e CF-e SAT).
Analise a imagem do cupom fiscal e extraia as informações abaixo.
Retorne SOMENTE um JSON válido, sem markdown, sem texto fora do JSON.

Estrutura esperada:
{
  "data": "YYYY-MM-DD",
  "valor": 0.00,
  "forma_pagamento": "texto exato (ex: Dinheiro, Pix, Cartão Crédito, Cartão Débito)",
  "itens": ["ITEM 1 – qtd x descrição – R$0,00", "ITEM 2 ..."],
  "descricao": "resumo dos itens principais separados por vírgula (máx 120 chars)",
  "cnpj": "XX.XXX.XXX/XXXX-XX",
  "estabelecimento": "razão social do emitente",
  "numero_cupom": "COO ou CCF ou número NF"
}

Regras:
- data: data de emissão no formato YYYY-MM-DD. Se tiver hora, ignore-a.
- valor: número decimal com ponto (ex: 5.50). Use o TOTAL do cupom.
- forma_pagamento: exatamente como aparece no cupom ("Dinheiro", "Pix", "Cartão Crédito", "Cartão Débito", "Crédito", "Débito").
- itens: lista cada produto/serviço do cupom com quantidade, descrição e valor unitário/total.
- descricao: join dos nomes dos produtos principais, curto o suficiente para um campo de observação.
- cnpj: CNPJ do estabelecimento emitente (não do consumidor). Null se não encontrado.
- estabelecimento: razão social ou nome fantasia do emitente. Null se não encontrado.
- numero_cupom: COO (Contador de Ordem de Operação) ou número do cupom/nota fiscal.
- Para campos não encontrados use null (strings) ou 0 (valor).
PROMPT;

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-haiku-4-5-20251001',
                'max_tokens' => 600,
                'messages'   => [[
                    'role'    => 'user',
                    'content' => [
                        [
                            'type'   => 'image',
                            'source' => [
                                'type'       => 'base64',
                                'media_type' => $mime,
                                'data'       => $base64,
                            ],
                        ],
                        ['type' => 'text', 'text' => $prompt],
                    ],
                ]],
            ]);

            if (!$response->successful()) {
                return ['erro' => 'Erro na API de visão: ' . $response->status()];
            }

            $text = $response->json('content.0.text', '');

            // Remove eventual markdown code fence
            $text = preg_replace('/^```(?:json)?\s*/i', '', trim($text));
            $text = preg_replace('/\s*```$/', '', $text);

            $dados = json_decode(trim($text), true);

            if (!is_array($dados)) {
                return ['erro' => 'Não foi possível interpretar o cupom. Tente o lançamento manual.'];
            }

            return $this->normalizarDadosClaude($dados);

        } catch (\Throwable $e) {
            return ['erro' => 'Falha ao processar imagem: ' . $e->getMessage()];
        }
    }

    /** Normaliza e valida os dados retornados pelo Claude */
    private function normalizarDadosClaude(array $d): array
    {
        // Data
        $data = null;
        if (!empty($d['data'])) {
            try { $data = (new \DateTime($d['data']))->format('Y-m-d'); } catch (\Throwable) {}
        }

        // Valor
        $valor = null;
        if (!empty($d['valor'])) {
            $v = str_replace(',', '.', (string) $d['valor']);
            $valor = floatval($v) ?: null;
        }

        // Itens: array ou string
        $itens = [];
        if (!empty($d['itens']) && is_array($d['itens'])) {
            $itens = array_values(array_filter($d['itens']));
        }

        return [
            'origem'          => 'ocr',
            'data'            => $data ?? now()->format('Y-m-d'),
            'valor'           => $valor,
            'forma_pagamento' => $d['forma_pagamento'] ?? null,
            'itens'           => $itens,
            'descricao'       => $d['descricao'] ?? null,
            'cnpj'            => $d['cnpj'] ?? null,
            'estabelecimento' => $d['estabelecimento'] ?? null,
            'numero_cupom'    => $d['numero_cupom'] ?? null,
        ];
    }

    // ────────────────────────────────────────────────────────────────────────
    //  MODO 1 — Parse QR NFC-e / CF-e SAT
    // ────────────────────────────────────────────────────────────────────────

    private function parseQRCode(string $qrCode): array
    {
        $chave = null;
        $valor = null;
        $data  = null;

        if (str_contains($qrCode, '?')) {
            parse_str(parse_url($qrCode, PHP_URL_QUERY) ?? '', $params);

            if (!empty($params['p'])) {
                [$chave, $valor, $data] = $this->parsePipeFormat($params['p']);
            } elseif (!empty($params['chNFe'])) {
                $chave = preg_replace('/\D/', '', $params['chNFe']);
                $valor = isset($params['vNF'])   ? $this->parseValor($params['vNF'])   : null;
                $data  = isset($params['dhEmi']) ? $this->parseDhEmi($params['dhEmi']) : null;
            }
        } else {
            [$chave, $valor, $data] = $this->parsePipeFormat($qrCode);
        }

        $cnpj = null;
        if ($chave && strlen($chave) >= 22) {
            $cnpjRaw = substr($chave, 6, 14);
            if (preg_match('/^\d{14}$/', $cnpjRaw)) {
                $cnpj = preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $cnpjRaw);
            }
        }

        $data  = $data ?? now()->format('Y-m-d');
        $estabelecimento = null;

        if ($cnpj) {
            $estabelecimento = $this->buscarNomeCNPJ(preg_replace('/\D/', '', $cnpj));
        }

        return [
            'origem'          => 'qrcode',
            'data'            => $data,
            'valor'           => $valor,
            'forma_pagamento' => null,
            'itens'           => [],
            'descricao'       => null,
            'cnpj'            => $cnpj,
            'estabelecimento' => $estabelecimento,
            'numero_cupom'    => null,
            'chave'           => $chave,
        ];
    }

    private function parsePipeFormat(string $p): array
    {
        $parts = explode('|', $p);
        $chave = null; $valor = null; $data = null;

        if (count($parts) >= 1) {
            $c = preg_replace('/\D/', '', $parts[0]);
            if (strlen($c) === 44) $chave = $c;
        }
        if (count($parts) >= 6) $valor = $this->parseValor($parts[5]);
        if (count($parts) >= 5) $data  = $this->parseDhEmi($parts[4]);

        return [$chave, $valor, $data];
    }

    private function parseValor(string $v): ?float
    {
        $f = floatval(str_replace(',', '.', trim($v)));
        return $f > 0 ? $f : null;
    }

    private function parseDhEmi(string $d): ?string
    {
        $d = urldecode(trim($d));
        if (empty($d)) return null;

        if (preg_match('/^[0-9a-fA-F]{5,10}$/', $d)) {
            $ts = hexdec($d);
            if ($ts > 1_000_000_000 && $ts < 2_000_000_000) return date('Y-m-d', $ts);
        }

        if (preg_match('/^(\d{4})(\d{2})(\d{2})\d{6}$/', $d, $m)) return "{$m[1]}-{$m[2]}-{$m[3]}";

        try { return (new \DateTime($d))->format('Y-m-d'); } catch (\Throwable) {}
        return null;
    }

    private function buscarNomeCNPJ(string $cnpj): ?string
    {
        try {
            $resp = Http::timeout(4)->get("https://publica.cnpj.ws/cnpj/{$cnpj}");
            if ($resp->successful()) {
                $d = $resp->json();
                return trim($d['estabelecimento']['nome_fantasia'] ?? $d['razao_social'] ?? '') ?: null;
            }
        } catch (\Throwable) {}
        return null;
    }
}
