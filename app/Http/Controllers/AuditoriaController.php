<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Unidade;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use OwenIt\Auditing\Models\Audit;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AuditoriaController extends Controller
{

    public function index(Request $request)
    {
        try {
            // Incluir modelos auditados
            $auditableTypes = [
                'App\\Models\\Unidade',
                'App\\Models\\AcessibilidadeUnidade',
                'App\\Models\\InformacoesUnidade',
                'App\\Models\\Midia',
                'App\\Models\\MidiaUnidade',
            ];

            $query = Audit::with(['user'])
                ->whereIn('auditable_type', $auditableTypes)
                ->orderBy('created_at', 'desc');

            // Filtros
            if ($request->filled('usuario_id')) {
                $query->where('user_id', $request->usuario_id);
            }

            if ($request->filled('evento')) {
                $query->where('event', $request->evento);
            }

            if ($request->filled('data_inicio')) {
                $query->whereDate('created_at', '>=', $request->data_inicio);
            }

            if ($request->filled('data_fim')) {
                $query->whereDate('created_at', '<=', $request->data_fim);
            }

            if ($request->filled('unidade_id')) {
                $unidadeId = $request->unidade_id;
                
                $query->where(function ($q) use ($unidadeId) {
                    // Auditorias da própria unidade
                    $q->where(function ($subQ) use ($unidadeId) {
                        $subQ->where('auditable_type', 'App\\Models\\Unidade')
                             ->where('auditable_id', $unidadeId);
                    })
                    // Auditorias de acessibilidade
                    ->orWhere(function ($subQ) use ($unidadeId) {
                        $subQ->where('auditable_type', 'App\\Models\\AcessibilidadeUnidade')
                             ->where(function ($jsonQ) use ($unidadeId) {
                                 $jsonQ->whereRaw("(new_values::json->>'unidade_id')::int = ?", [$unidadeId])
                                       ->orWhereRaw("(old_values::json->>'unidade_id')::int = ?", [$unidadeId]);
                             });
                    })
                    // Auditorias de informações estruturais
                    ->orWhere(function ($subQ) use ($unidadeId) {
                        $subQ->where('auditable_type', 'App\\Models\\InformacoesUnidade')
                             ->where(function ($jsonQ) use ($unidadeId) {
                                 $jsonQ->whereRaw("(new_values::json->>'unidade_id')::int = ?", [$unidadeId])
                                       ->orWhereRaw("(old_values::json->>'unidade_id')::int = ?", [$unidadeId]);
                             });
                    })
                    // Auditorias de mídias
                    ->orWhere(function ($subQ) use ($unidadeId) {
                        $subQ->where('auditable_type', 'App\\Models\\MidiaUnidade')
                             ->where(function ($jsonQ) use ($unidadeId) {
                                 $jsonQ->whereRaw("(new_values::json->>'unidade_id')::int = ?", [$unidadeId])
                                       ->orWhereRaw("(old_values::json->>'unidade_id')::int = ?", [$unidadeId]);
                             });
                    });
                });
            }

            // Filtro por tipo de modelo
            if ($request->filled('modelo_tipo')) {
                $query->where('auditable_type', $request->modelo_tipo);
            }

            $auditorias = $query->paginate(20)->through(function ($audit) {
                return $this->formatarAuditoria($audit);
            });

            // Buscar usuários para filtro
            $usuarios = User::select('id', 'name', 'matricula')
                ->orderBy('name')
                ->get();

            // Buscar unidades para filtro
            $unidades = Unidade::select('id', 'nome')
                ->orderBy('nome')
                ->get();

            // Tipos de modelo para filtro
            $modeloTipos = [
                'App\\Models\\Unidade' => 'Unidades',
                'App\\Models\\AcessibilidadeUnidade' => 'Acessibilidade',
                'App\\Models\\InformacoesUnidade' => 'Informações Estruturais',
                'App\\Models\\Midia' => 'Mídias',
                'App\\Models\\MidiaUnidade' => 'Associações de Mídia',
            ];

            return Inertia::render('Admin/Auditoria/Index', [
                'auditorias' => $auditorias,
                'usuarios' => $usuarios,
                'unidades' => $unidades,
                'modeloTipos' => $modeloTipos,
                'filtros' => $request->only(['usuario_id', 'evento', 'data_inicio', 'data_fim', 'unidade_id', 'modelo_tipo'])
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao carregar auditorias', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Erro ao carregar auditorias: ' . $e->getMessage());
        }
    }

    public function show(Unidade $unidade)
    {
        try {
            // Incluir todas as auditorias relacionadas à unidade
            $auditorias = Audit::with(['user'])
                ->where(function ($query) use ($unidade) {
                    // Auditorias da própria unidade
                    $query->where(function ($q) use ($unidade) {
                        $q->where('auditable_type', 'App\\Models\\Unidade')
                          ->where('auditable_id', $unidade->id);
                    });
                    
                    // Auditorias de acessibilidade
                    if ($unidade->acessibilidade) {
                        $query->orWhere(function ($q) use ($unidade) {
                            $q->where('auditable_type', 'App\\Models\\AcessibilidadeUnidade')
                              ->where('auditable_id', $unidade->acessibilidade->id);
                        });
                    }
                    
                    // Auditorias de informaçõe
                    if ($unidade->informacoes) {
                        $query->orWhere(function ($q) use ($unidade) {
                            $q->where('auditable_type', 'App\\Models\\InformacoesUnidade')
                              ->where('auditable_id', $unidade->informacoes->id);
                        });
                    }
                    
                    // Auditorias de mídias
                    $midiaIds = $unidade->midias->pluck('id')->toArray();
                    if (!empty($midiaIds)) {
                        $query->orWhere(function ($q) use ($midiaIds) {
                            $q->where('auditable_type', 'App\\Models\\Midia')
                              ->whereIn('auditable_id', $midiaIds);
                        });
                    }
                    
                    // Auditorias de associações mídia-unidade
                    $query->orWhere(function ($q) use ($unidade) {
                        $q->where('auditable_type', 'App\\Models\\MidiaUnidade')
                          ->where(function ($jsonQ) use ($unidade) {
                              $jsonQ->whereRaw("(new_values::json->>'unidade_id')::int = ?", [$unidade->id])
                                    ->orWhereRaw("(old_values::json->>'unidade_id')::int = ?", [$unidade->id]);
                          });
                    });
                })
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($audit) {
                    return $this->formatarAuditoria($audit);
                });

            return Inertia::render('Admin/Auditoria/Show', [
                'unidade' => $unidade->load(['team', 'acessibilidade', 'informacoes', 'midias']),
                'auditorias' => $auditorias
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao carregar auditoria da unidade', [
                'unidade_id' => $unidade->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Erro ao carregar auditoria da unidade: ' . $e->getMessage());
        }
    }

    private function formatarAuditoria($audit)
    {
        try {
            // Configurar timezone
            $dataHoraBrasil = Carbon::parse($audit->created_at)
                ->setTimezone('America/Sao_Paulo')
                ->format('d/m/Y H:i:s');

            return [
                'id' => $audit->id,
                'evento' => $this->formatarEvento($audit->event),
                'modelo_tipo' => $this->formatarModeloTipo($audit->auditable_type),
                'modelo_id' => $audit->auditable_id,
                'unidade_id' => $this->getUnidadeId($audit),
                'unidade_nome' => $this->getUnidadeNome($audit),
                'descricao_item' => $this->getDescricaoItem($audit),
                'usuario' => $audit->user ? $audit->user->name : 'Sistema',
                'usuario_matricula' => $audit->user ? $audit->user->matricula : null,
                'data_hora' => $dataHoraBrasil,
                'ip_address' => $audit->ip_address,
                'user_agent' => $audit->user_agent,
                'url' => $audit->url ?? null,
                'alteracoes' => $this->formatarAlteracoes($audit),
                'tags' => $this->formatarTags($audit->tags ?? ''),
                'valores_antigos' => $audit->old_values ?? [],
                'valores_novos' => $audit->new_values ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao formatar auditoria', [
                'audit_id' => $audit->id,
                'message' => $e->getMessage()
            ]);
            
            // Retornar estrutura mínima em caso de erro
            return [
                'id' => $audit->id,
                'evento' => 'Erro',
                'modelo_tipo' => 'Desconhecido',
                'modelo_id' => $audit->auditable_id,
                'unidade_id' => null,
                'unidade_nome' => 'Erro ao carregar',
                'descricao_item' => 'Erro',
                'usuario' => $audit->user ? $audit->user->name : 'Sistema',
                'usuario_matricula' => $audit->user ? $audit->user->matricula : null,
                'data_hora' => Carbon::parse($audit->created_at)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s'),
                'ip_address' => $audit->ip_address,
                'user_agent' => $audit->user_agent,
                'url' => $audit->url ?? null,
                'alteracoes' => [],
                'tags' => [],
                'valores_antigos' => [],
                'valores_novos' => [],
            ];
        }
    }

    private function formatarModeloTipo($modeloTipo)
    {
        $tipos = [
            'App\\Models\\Unidade' => 'Unidade',
            'App\\Models\\AcessibilidadeUnidade' => 'Acessibilidade',
            'App\\Models\\InformacoesUnidade' => 'Informações Estruturais',
            'App\\Models\\Midia' => 'Mídia',
            'App\\Models\\MidiaUnidade' => 'Associação Mídia',
        ];

        return $tipos[$modeloTipo] ?? 'Desconhecido';
    }

    private function getUnidadeId($audit)
    {
        if ($audit->auditable_type === 'App\\Models\\Unidade') {
            return $audit->auditable_id;
        }

        // Para outros modelos, tentar extrair unidade_id dos valores
        try {
            if (isset($audit->new_values) && is_string($audit->new_values)) {
                $newValues = json_decode($audit->new_values, true);
                if (isset($newValues['unidade_id'])) {
                    return $newValues['unidade_id'];
                }
            } elseif (is_array($audit->new_values) && isset($audit->new_values['unidade_id'])) {
                return $audit->new_values['unidade_id'];
            }

            if (isset($audit->old_values) && is_string($audit->old_values)) {
                $oldValues = json_decode($audit->old_values, true);
                if (isset($oldValues['unidade_id'])) {
                    return $oldValues['unidade_id'];
                }
            } elseif (is_array($audit->old_values) && isset($audit->old_values['unidade_id'])) {
                return $audit->old_values['unidade_id'];
            }
        } catch (\Exception $e) {
            Log::warning('Erro ao extrair unidade_id dos valores JSON', [
                'audit_id' => $audit->id,
                'message' => $e->getMessage()
            ]);
        }

        return null;
    }

    private function getDescricaoItem($audit)
    {
        switch ($audit->auditable_type) {
            case 'App\\Models\\Unidade':
                return $this->getUnidadeNome($audit);
            
            case 'App\\Models\\AcessibilidadeUnidade':
                return 'Dados de Acessibilidade';
            
            case 'App\\Models\\InformacoesUnidade':
                return 'Informações Estruturais';
            
            case 'App\\Models\\Midia':
                // Tentar pegar o tipo de mídia
                try {
                    $newValues = is_string($audit->new_values) ? json_decode($audit->new_values, true) : $audit->new_values;
                    if (isset($newValues['midia_tipo_id'])) {
                        $midiaTipo = \App\Models\MidiaTipo::find($newValues['midia_tipo_id']);
                        return 'Mídia: ' . ($midiaTipo ? $midiaTipo->nome : 'Tipo desconhecido');
                    }
                } catch (\Exception $e) {
                }
                return 'Mídia (ID: ' . $audit->auditable_id . ')';
            
            case 'App\\Models\\MidiaUnidade':
                return 'Associação de Mídia';
            
            default:
                return 'Item ID: ' . $audit->auditable_id;
        }
    }

    private function getUnidadeNome($audit)
    {
        // Para auditorias de unidade
        if ($audit->auditable_type === 'App\\Models\\Unidade') {
            try {
                // Tentar pegar o nome da unidade dos valores novos primeiro
                $newValues = is_string($audit->new_values) ? json_decode($audit->new_values, true) : $audit->new_values;
                if (isset($newValues['nome'])) {
                    return $newValues['nome'];
                }
            } catch (\Exception $e) {
            }

            // Senão, buscar a unidade atual
            try {
                $unidade = Unidade::find($audit->auditable_id);
                return $unidade ? $unidade->nome : 'Unidade não encontrada';
            } catch (\Exception $e) {
                Log::warning('Erro ao buscar nome da unidade', [
                    'unidade_id' => $audit->auditable_id,
                    'message' => $e->getMessage()
                ]);
                return 'N/A';
            }
        }

        // Para outros modelos, tentar encontrar a unidade relacionada
        $unidadeId = $this->getUnidadeId($audit);
        if ($unidadeId) {
            try {
                $unidade = Unidade::find($unidadeId);
                return $unidade ? $unidade->nome : 'Unidade não encontrada';
            } catch (\Exception $e) {
                return 'N/A';
            }
        }

        return 'N/A';
    }

    private function formatarEvento($evento)
    {
        $eventos = [
            'created' => 'Criação',
            'updated' => 'Atualização',
            'deleted' => 'Exclusão',
            'restored' => 'Restauração',
        ];

        return $eventos[$evento] ?? $evento;
    }

    private function formatarAlteracoes($audit)
    {
        $alteracoes = [];
        
        try {
            $newValues = is_string($audit->new_values) ? json_decode($audit->new_values, true) : $audit->new_values;
            $oldValues = is_string($audit->old_values) ? json_decode($audit->old_values, true) : $audit->old_values;

            if ($audit->event === 'created') {
                foreach (($newValues ?? []) as $campo => $valor) {
                    $alteracoes[] = [
                        'campo' => $this->formatarNomeCampo($campo),
                        'valor_antigo' => null,
                        'valor_novo' => $valor,
                        'tipo' => 'criacao'
                    ];
                }
            } elseif ($audit->event === 'updated') {
                foreach (($newValues ?? []) as $campo => $valorNovo) {
                    $valorAntigo = ($oldValues ?? [])[$campo] ?? null;
                    if ($valorAntigo !== $valorNovo) {
                        $alteracoes[] = [
                            'campo' => $this->formatarNomeCampo($campo),
                            'valor_antigo' => $valorAntigo,
                            'valor_novo' => $valorNovo,
                            'tipo' => 'atualizacao'
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Erro ao formatar alterações', [
                'audit_id' => $audit->id,
                'message' => $e->getMessage()
            ]);
        }

        return $alteracoes;
    }

    private function formatarNomeCampo($campo)
    {
        $campos = [
            // Campos da Unidade
            'nome' => 'Nome da Unidade',
            'codigo' => 'Código da Unidade',
            'status' => 'Status',
            'tipo_estrutural' => 'Tipo Estrutural',
            'tipo_judicial' => 'Tipo Judicial',
            'cidade' => 'Cidade',
            'rua' => 'Rua',
            'numero' => 'Número',
            'bairro' => 'Bairro',
            'cep' => 'CEP',
            'latitude' => 'Latitude',
            'longitude' => 'Longitude',
            'rejection_reason' => 'Motivo da Reprovação',
            'orgao_cedente' => 'Órgão Cedente',
            'termo_cessao' => 'Termo de Cessão',
            'prazo_cessao' => 'Prazo de Cessão',
            'is_draft' => 'Status de Rascunho',
            'imovel_compartilhado_orgao' => 'Imóvel Compartilhado',
            'observacoes' => 'Observações',
            
            // Campos da Acessibilidade
            'rampa_acesso' => 'Rampa de Acesso',
            'corrimao' => 'Corrimão',
            'piso_tatil' => 'Piso Tátil',
            'banheiro_adaptado' => 'Banheiro Adaptado',
            'elevador' => 'Elevador',
            'sinalizacao_braile' => 'Sinalização Braile',
            
            // Campos das Informações Estruturais
            'pavimentacao_rua' => 'Pavimentação da Rua',
            'tipo_imovel' => 'Tipo do Imóvel',
            'qtd_pavimentos' => 'Quantidade de Pavimentos',
            'cercado_muros' => 'Cercado/Muros',
            'estacionamento_interno' => 'Estacionamento Interno',
            'estacionamento_externo' => 'Estacionamento Externo',
            'qtd_recepcao' => 'Quantidade de Recepções',
            'qtd_wc_publico' => 'Quantidade de WCs Públicos',
            'qtd_gabinetes' => 'Quantidade de Gabinetes',
            'qtd_sala_oitiva' => 'Quantidade de Salas de Oitiva',
            'qtd_wc_servidores' => 'Quantidade de WCs Servidores',
            'qtd_alojamento_masculino' => 'Quantidade de Alojamentos Masculinos',
            'qtd_alojamento_feminino' => 'Quantidade de Alojamentos Femininos',
            'qtd_xadrez_masculino' => 'Quantidade de Xadrezes Masculinos',
            'qtd_xadrez_feminino' => 'Quantidade de Xadrezes Femininos',
            'area_aproximada_unidade' => 'Área Aproximada da Unidade',
            'area_aproximada_terreno' => 'Área Aproximada do Terreno',
            'piso' => 'Tipo de Piso',
            'parede' => 'Tipo de Parede',
            'cobertura' => 'Tipo de Cobertura',
            'tem_espaco_veiculos_apreendidos' => 'Tem Espaço para Veículos Apreendidos',
            'qtd_max_veiculos_automovel' => 'Quantidade Máxima de Veículos',
            
            // Campos da Mídia
            'midia_tipo_id' => 'Tipo de Mídia (ID)',
            'path' => 'Caminho do Arquivo',
            'mime_type' => 'Tipo de Arquivo',
            'tamanho' => 'Tamanho do Arquivo',
            
            // Campos da MidiaUnidade
            'nao_possui_ambiente' => 'Não Possui Ambiente',
            'unidade_id' => 'ID da Unidade',
            'midia_id' => 'ID da Mídia',
        ];

        return $campos[$campo] ?? ucfirst(str_replace('_', ' ', $campo));
    }

    private function formatarTags($tags)
    {
        if (empty($tags)) {
            return [];
        }

        // Se as tags estão como string separada por vírgula
        if (is_string($tags)) {
            return array_filter(explode(',', $tags));
        }

        // Se já é um array
        if (is_array($tags)) {
            return $tags;
        }

        return [];
    }
}