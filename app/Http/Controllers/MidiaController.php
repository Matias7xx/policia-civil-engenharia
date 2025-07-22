<?php

namespace App\Http\Controllers;

use App\Models\Midia;
use App\Models\Unidade;
use App\Models\MidiaUnidade;
use App\Models\MidiaTipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\StorageHelper;

class MidiaController extends Controller
{

    protected function processMidiaReplaceValue($value) {
        if ($value === '1' || $value === 'true' || $value === true || $value === 1) {
            return true;
        }
        return false;
    }

    public function store(Request $request)
    {
        $request->validate([
            'unidade_id' => 'required|exists:unidades,id',
            'files' => 'nullable|array',
            'files.*' => 'required|file|mimes:jpg,jpeg,png|max:10240',
            'midia_tipos' => 'nullable|array',
            'midia_tipos.*' => 'required|exists:midia_tipos,id',
            'midia_replace' => 'array',
            'midia_replace.*' => 'nullable|boolean',
            'midia_remover' => 'array',
            'midia_remover.*' => 'exists:midias,id',
            'ambientes_nao_possui' => 'nullable|array',
            'ambientes_nao_possui.*' => 'nullable',
        ], [
            'files.*.mimes' => 'Os arquivos devem ser do tipo JPG ou PNG.',
            'files.*.max' => 'Os arquivos não podem exceder 10MB.',
            'files.*required' => 'Preencha todos os campos marcados com "*"'
        ]);

        try {
            $unidade = Unidade::findOrFail($request->unidade_id);
            $files = $request->file('files') ?? [];
            $midiaTipos = $request->midia_tipos ?? [];
            $midiasReplace = $request->midia_replace ?? [];
            $midiasRemover = $request->midia_remover ?? [];
            $ambientesNaoPossui = $request->ambientes_nao_possui ?? [];

            //Verificar quantidade de fotos por tipo (mínimo 2 e máximo 3)
            $this->validatePhotoQuantity($unidade, $files, $midiaTipos, $midiasRemover, $midiasReplace);

            // Processar exclusões de mídias
            if (!empty($midiasRemover)) {
                foreach ($midiasRemover as $midiaId) {
                    $midia = Midia::find($midiaId);
                    if ($midia) {
                        $pertenceUnidade = MidiaUnidade::where('unidade_id', $unidade->id)
                            ->where('midia_id', $midia->id)
                            ->exists();
                        
                        if ($pertenceUnidade) {
                            // Remover arquivo do MinIO
                            if ($midia->path !== 'nao_possui_ambiente') {
                                StorageHelper::removerArquivo($midia->path);
                            }
                            
                            // Remover relacionamento
                            MidiaUnidade::where('midia_id', $midia->id)
                                        ->where('unidade_id', $unidade->id)
                                        ->delete();
                            
                            // Remover registro da mídia se não tem outras unidades
                            if (!$midia->unidades()->exists()) {
                                $midia->delete();
                            }
                        }
                    }
                }
            }

            // Processar dados de "não possui ambiente"
            $this->processarAmbientesNaoPossui($unidade, $ambientesNaoPossui);

            // Se não há arquivos para processar, apenas retornar sucesso
            if (empty($files)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Alterações processadas com sucesso.',
                ]);
            }

            // Processar upload dos arquivos
            foreach ($files as $index => $file) {
                $tipoId = $midiaTipos[$index];
                $shouldReplace = isset($midiasReplace[$index]) && 
                $this->processMidiaReplaceValue($midiasReplace[$index]);
                
                // Se for substituição, remover mídias existentes deste tipo
                if ($shouldReplace) {
                    // Verificar se mídia desse tipo foi removida
                    static $typesAlreadyReplaced = [];
                    if (!in_array($tipoId, $typesAlreadyReplaced)) {
                        $this->removeExistingMediaForType($unidade, $tipoId, $midiasRemover);
                        $typesAlreadyReplaced[] = $tipoId;
                    }
                }
                
                // Fazer upload do arquivo
                $this->uploadMediaFile($unidade, $file, $tipoId, $index);
            }

            // Verificar se é finalização (unidade em rascunho)
            $isFinalizing = $unidade->is_draft === true;
            
            // Se for finalização, atualizar status da unidade
            if ($isFinalizing) {
                // Verificar se todas as validações foram atendidas
                $this->validateUnidadeForFinalization($unidade);
                
                // Finalizar unidade
                $unidade->update([
                    'is_draft' => false,
                    'status' => 'pendente_avaliacao'
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Cadastro finalizado com sucesso! Redirecionando...',
                    'redirect' => route('unidades.show', [
                        'team' => $unidade->team_id,
                        'unidade' => $unidade->id
                    ])
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Mídias salvas com sucesso!',
            ]);

        } catch (\Exception $e) {
            \Log::error('Erro ao salvar mídias: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor. Tente novamente.',
            ], 500);
        }
    }

    /**
     * Validar se a unidade está pronta para finalização
     */
    private function validateUnidadeForFinalization($unidade)
    {
        // Verificar se todas as abas estão preenchidas
        if (!$unidade->acessibilidade || !$unidade->informacoes) {
            throw new \Exception('Todas as abas devem ser preenchidas antes de finalizar o cadastro.');
        }

        // Verificar se todas as mídias obrigatórias existem
        $tiposObrigatorios = ['foto_frente', 'foto_lateral_1', 'foto_lateral_2', 'foto_fundos', 
                              'foto_medidor_agua', 'foto_medidor_energia'];
        
        $tiposExistentes = $unidade->midias()
            ->join('midia_tipos', 'midias.midia_tipo_id', '=', 'midia_tipos.id')
            ->where('midias.path', '!=', 'nao_possui_ambiente')
            ->pluck('midia_tipos.nome')
            ->toArray();
        
        $tiposFaltantes = array_diff($tiposObrigatorios, $tiposExistentes);
        
        if (!empty($tiposFaltantes)) {
            throw new \Exception('Faltam fotos obrigatórias: ' . implode(', ', $tiposFaltantes));
        }

        // Verificar quantidade mínima de fotos por tipo obrigatório
        foreach ($tiposObrigatorios as $tipoNome) {
            $count = $unidade->midias()
                ->join('midia_tipos', 'midias.midia_tipo_id', '=', 'midia_tipos.id')
                ->where('midia_tipos.nome', $tipoNome)
                ->where('midias.path', '!=', 'nao_possui_ambiente')
                ->count();
            
            if ($count < 2) {
                throw new \Exception("O tipo '$tipoNome' deve ter no mínimo 2 fotos.");
            }
        }
    }

    /**
     * Validar quantidade de fotos por tipo (2-3)
     */
    private function validatePhotoQuantity($unidade, $files, $midiaTipos, $midiasRemover, $midiasReplace)
    {
        // Agrupar arquivos por tipo
        $filesByType = [];
        foreach ($files as $index => $file) {
            $tipoId = $midiaTipos[$index];
            if (!isset($filesByType[$tipoId])) {
                $filesByType[$tipoId] = 0;
            }
            $filesByType[$tipoId]++;
        }

        // Verificar cada tipo que terá fotos
        foreach ($filesByType as $tipoId => $newCount) {
            $midiaTipo = MidiaTipo::find($tipoId);
            if (!$midiaTipo) continue;

            // Contar fotos existentes que não serão removidas
            $existingCount = Midia::whereHas('unidades', function($query) use ($unidade) {
                $query->where('unidades.id', $unidade->id)
                    ->where('midia_unidade.nao_possui_ambiente', false);
            })->where('midia_tipo_id', $tipoId)
            ->whereNotIn('id', $midiasRemover)
            ->count();

            // Verificar se será substituição total
            $willReplace = false;
            foreach ($midiasReplace as $index => $replace) {
                if (isset($midiaTipos[$index]) && $midiaTipos[$index] == $tipoId && 
                    $this->processMidiaReplaceValue($replace)) {
                    $willReplace = true;
                    break;
                }
            }

            // Calcular total final
            $finalCount = $willReplace ? $newCount : ($existingCount + $newCount);

            // Só validar o mínimo (2) se não há fotos existentes e não é área interna opcional
            $isRequired = in_array($midiaTipo->nome, [
                'foto_frente', 'foto_lateral_1', 'foto_lateral_2', 'foto_fundos', 
                'foto_medidor_agua', 'foto_medidor_energia'
            ]);
            
            $isAreaInterna = !in_array($midiaTipo->nome, [
                'foto_frente', 'foto_lateral_1', 'foto_lateral_2', 'foto_fundos', 
                'foto_medidor_agua', 'foto_medidor_energia', 'rampa_acesso', 
                'corrimao', 'piso_tatil', 'banheiro_adaptado', 'elevador', 
                'sinalizacao_braile'
            ]);

            // Validar limites apenas se há fotos ou é obrigatório
            if ($finalCount > 0) {
                if ($finalCount > 3) {
                    throw new \Exception("O tipo '{$midiaTipo->nome}' pode ter no máximo 3 fotos. Atual: {$finalCount}");
                }
                
                // Só exigir mínimo de 2 para tipos obrigatórios ou que já têm fotos
                if (($isRequired || $existingCount > 0) && $finalCount < 2) {
                    throw new \Exception("O tipo '{$midiaTipo->nome}' deve ter no mínimo 2 fotos. Atual: {$finalCount}");
                }
            }
        }

        //Verificar se tipos obrigatórios têm pelo menos uma foto sendo enviada
        $tiposObrigatorios = ['foto_frente', 'foto_lateral_1', 'foto_lateral_2', 'foto_fundos', 
                              'foto_medidor_agua', 'foto_medidor_energia'];
        
        foreach ($tiposObrigatorios as $tipoNome) {
            $tipoModel = MidiaTipo::where('nome', $tipoNome)->first();
            if (!$tipoModel) continue;
            
            $tipoId = $tipoModel->id;
            
            // Se não está nos arquivos novos, verificar se tem fotos existentes suficientes
            if (!isset($filesByType[$tipoId])) {
                $existingCount = Midia::whereHas('unidades', function($query) use ($unidade) {
                    $query->where('unidades.id', $unidade->id)
                        ->where('midia_unidade.nao_possui_ambiente', false);
                })->where('midia_tipo_id', $tipoId)
                ->whereNotIn('id', $midiasRemover)
                ->count();
                                
                if ($existingCount < 2) {
                    throw new \Exception("O tipo '$tipoNome' deve ter no mínimo 2 fotos. Atual: {$existingCount}");
                }
            }
        }
    }

    private function removeExistingMediaForType($unidade, $tipoId, $midiasRemover)
    {
        $midiasExistentes = Midia::whereHas('unidades', function($query) use ($unidade) {
            $query->where('unidades.id', $unidade->id)
                ->where('midia_unidade.nao_possui_ambiente', false);
        })->where('midia_tipo_id', $tipoId)
        ->whereNotIn('id', $midiasRemover)
        ->get();
        
        foreach ($midiasExistentes as $midiaExistente) {
            // Remover arquivo do MinIO
            if ($midiaExistente->path !== 'nao_possui_ambiente') {
                StorageHelper::removerArquivo($midiaExistente->path);
            }
            
            // Remover relacionamento
            MidiaUnidade::where('midia_id', $midiaExistente->id)
                        ->where('unidade_id', $unidade->id)
                        ->delete();
            
            // Remover registro da mídia se não tem outras unidades
            if (!$midiaExistente->unidades()->exists()) {
                $midiaExistente->delete();
            }
        }
    }

    /**
     * Fazer upload de um arquivo de mídia
     */
    private function uploadMediaFile($unidade, $file, $tipoId, $index)
    {
        try {
            
            // Obter o nome do tipo de mídia para o arquivo
            $midiaTipo = MidiaTipo::find($tipoId);
            $timestamp = now()->format('YmdHis');
            $randomString = \Str::random(6);
            $extension = $file->getClientOriginalExtension();
            
            // Nome único: tipo_timestamp_random_index.extensao
            $nomeArquivo = $midiaTipo ? 
                "{$midiaTipo->nome}_{$timestamp}_{$randomString}_{$index}.{$extension}" : 
                "midia_{$timestamp}_{$randomString}_{$index}.{$extension}";

            // Fazer upload para MinIO
            $path = StorageHelper::salvarFoto($unidade, $file, $nomeArquivo);

            // Criar registro da mídia
            $midia = Midia::create([
                'midia_tipo_id' => $tipoId,
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'tamanho' => $file->getSize(),
            ]);

            // Associar à unidade
            $midiaUnidade = MidiaUnidade::create([
                'unidade_id' => $unidade->id,
                'midia_id' => $midia->id,
                'nao_possui_ambiente' => false,
            ]);
            
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Processa os dados de ambientes que a unidade não possui
     */
    private function processarAmbientesNaoPossui($unidade, $ambientesNaoPossui)
    {
        if (empty($ambientesNaoPossui)) {
            return;
        }

        // Buscar tipos de mídia de área interna
        $tiposAreaInterna = DB::table('midia_tipos')
            ->whereNotIn('nome', [
                'foto_frente', 'foto_lateral_1', 'foto_lateral_2', 'foto_fundos', 
                'foto_medidor_agua', 'foto_medidor_energia', 'rampa_acesso', 
                'corrimao', 'piso_tatil', 'banheiro_adaptado', 'elevador', 
                'sinalizacao_braile'
            ])
            ->pluck('id')
            ->toArray();

        foreach ($ambientesNaoPossui as $tipoId => $naoPossui) {
            if (!in_array($tipoId, $tiposAreaInterna)) {
                continue;
            }

            if ($naoPossui === '1' || $naoPossui === true) {
                // Remover mídias reais existentes deste tipo para esta unidade
                $midiasExistentes = Midia::whereHas('unidades', function($query) use ($unidade) {
                    $query->where('unidades.id', $unidade->id)
                          ->where('midia_unidade.nao_possui_ambiente', false);
                })->where('midia_tipo_id', $tipoId)->get();

                foreach ($midiasExistentes as $midia) {
                    // Remover arquivo do MinIO
                    if ($midia->path !== 'nao_possui_ambiente') {
                        StorageHelper::removerArquivo($midia->path);
                    }
                    
                    // Remover relacionamento
                    MidiaUnidade::where('midia_id', $midia->id)
                                ->where('unidade_id', $unidade->id)
                                ->delete();
                    
                    // Remover registro da mídia se não tem outras unidades
                    if (!$midia->unidades()->exists()) {
                        $midia->delete();
                    }
                }

                // Criar registro de "não possui ambiente"
                $this->salvarInformacaoNaoPossui($unidade->id, $tipoId);
            } else {
                // Se foi desmarcado, remover o registro de "não possui"
                $this->removerInformacaoNaoPossui($unidade->id, $tipoId);
            }
        }
    }

    /**
     * Salva a informação de que a unidade não possui determinado ambiente
     */
    private function salvarInformacaoNaoPossui($unidadeId, $tipoId)
    {
        $existeRegistro = MidiaUnidade::where('unidade_id', $unidadeId)
            ->whereHas('midia', function($query) use ($tipoId) {
                $query->where('midia_tipo_id', $tipoId);
            })
            ->where('nao_possui_ambiente', true)
            ->exists();

        if (!$existeRegistro) {
            // Criar uma mídia "dummy" para representar o "não possui"
            $midia = Midia::create([
                'midia_tipo_id' => $tipoId,
                'path' => 'nao_possui_ambiente',
                'mime_type' => 'application/x-empty',
                'tamanho' => 0,
            ]);

            // Criar relacionamento marcado como "não possui ambiente"
            MidiaUnidade::create([
                'unidade_id' => $unidadeId,
                'midia_id' => $midia->id,
                'nao_possui_ambiente' => true,
                'observacoes' => 'Unidade não possui este ambiente',
            ]);
        }
    }

    /**
     * Remove a informação de que a unidade não possui determinado ambiente
     */
    private function removerInformacaoNaoPossui($unidadeId, $tipoId)
    {
        $registrosNaoPossui = MidiaUnidade::where('unidade_id', $unidadeId)
            ->where('nao_possui_ambiente', true)
            ->whereHas('midia', function($query) use ($tipoId) {
                $query->where('midia_tipo_id', $tipoId);
            })
            ->get();

        foreach ($registrosNaoPossui as $registro) {
            $midia = $registro->midia;
            
            // Remover relacionamento
            $registro->delete();
            
            // Remover mídia "dummy" se não tem outras unidades
            if ($midia && !$midia->unidades()->exists()) {
                $midia->delete();
            }
        }
    }

    // Método para atualização de mídias (reutiliza a lógica do store mas com adaptações para edição)
    public function update(Request $request, $unidade_id)
    {
        // Atualizar o unidade_id no request
        $request->merge(['unidade_id' => $unidade_id]);
        
        return $this->store($request);
    }

    // Método para excluir uma mídia específica
    public function destroy($id)
    {
        try {
            $midia = Midia::findOrFail($id);
            
            // Remover arquivo do MinIO apenas se não for um registro NAO_POSSUI
            if ($midia->path !== 'nao_possui_ambiente') {
                StorageHelper::removerArquivo($midia->path);
            }
            
            // Remover relacionamento
            MidiaUnidade::where('midia_id', $id)->delete();
            
            // Remover registro
            $midia->delete();
            
            return response()->json(['success' => true, 'message' => 'Mídia excluída com sucesso.']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro interno ao excluir a mídia: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Visualizar imagem
     */
    public function view($id)
    {
        try {
            $midia = Midia::findOrFail($id);
            
            if ($midia->path === 'nao_possui_ambiente') {
                abort(404, 'Arquivo não encontrado');
            }

            if (!StorageHelper::arquivoExiste($midia->path)) {
                abort(404, 'Arquivo não encontrado no servidor');
            }

            $conteudo = StorageHelper::obterArquivo($midia->path);
            
            return response($conteudo, 200, [
                'Content-Type' => $midia->mime_type,
                'Cache-Control' => 'public, max-age=3600',
            ]);
            
        } catch (\Exception $e) {
            abort(500, 'Erro ao carregar arquivo');
        }
    }

    /**
     * Download de arquivo
     */
    public function download($id)
    {
        try {
            $midia = Midia::findOrFail($id);
            
            if ($midia->path === 'nao_possui_ambiente') {
                abort(404, 'Arquivo não encontrado');
            }

            if (!StorageHelper::arquivoExiste($midia->path)) {
                abort(404, 'Arquivo não encontrado no servidor');
            }

            $conteudo = StorageHelper::obterArquivo($midia->path);
            $nomeArquivo = basename($midia->path);
            
            return response($conteudo, 200, [
                'Content-Type' => $midia->mime_type,
                'Content-Disposition' => 'attachment; filename="' . $nomeArquivo . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]);
            
        } catch (\Exception $e) {
            abort(500, 'Erro ao baixar arquivo');
        }
    }

    //Atualizar as mídias na view sem precisar de refresh
    public function getMidias($unidade_id)
    {
        try {
            $unidade = Unidade::with(['midias.midiaTipo'])->findOrFail($unidade_id);
            
            // Formatar as mídias para o frontend
            $midias = $unidade->midias->map(function ($midia) {
                return [
                    'id' => $midia->id,
                    'path' => $midia->path,
                    'url' => $midia->url,
                    'mime_type' => $midia->mime_type,
                    'tamanho' => $midia->tamanho,
                    'midia_tipo_id' => $midia->midia_tipo_id,
                    'midiaTipo' => $midia->midiaTipo,
                    'pivot' => [
                        'nao_possui_ambiente' => $midia->pivot->nao_possui_ambiente ?? false,
                    ],
                    'created_at' => $midia->created_at,
                    'updated_at' => $midia->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'midias' => $midias
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar mídias da unidade.'
            ], 500);
        }
    }
}