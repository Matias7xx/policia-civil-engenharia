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
    /**
     * Processar valor de substituição de mídia
     */
    protected function processMidiaReplaceValue($value) 
    {
        if ($value === '1' || $value === 'true' || $value === true || $value === 1) {
            return true;
        }
        return false;
    }

    /**
     * Armazenar/atualizar mídias
     */
    public function store(Request $request)
    {
        // Aumentar limites para uploads grandes
        set_time_limit(600); // 10 minutos
        ini_set('memory_limit', '1G');
        
        $request->validate([
            'unidade_id' => 'required|exists:unidades,id',
            'files' => 'nullable|array|max:30', // 30 arquivos por lote (parte dos 129)
            'files.*' => 'required|file|mimes:jpg,jpeg,png|max:10240', // 10MB cada (10×129=1.3GB total)
            'midia_tipos' => 'nullable|array',
            'midia_tipos.*' => 'required|exists:midia_tipos,id',
            'midia_replace' => 'array',
            'midia_replace.*' => 'nullable|boolean',
            'midia_remover' => 'array',
            'midia_remover.*' => 'exists:midias,id',
            'ambientes_nao_possui' => 'nullable|array',
            'ambientes_nao_possui.*' => 'nullable',
            //Flag para indicar se é upload em lotes
            'is_batch_upload' => 'nullable|in:0,1,true,false',
            'total_files_in_batch' => 'nullable|integer',
        ], [
            'files.max' => 'Máximo de 25 arquivos por lote.',
            'files.*.mimes' => 'Os arquivos devem ser do tipo JPG ou PNG.',
            'files.*.max' => 'Os arquivos não podem exceder 10MB.',
            'files.*required' => 'Preencha todos os campos marcados com "*"'
        ]);

        try {
            DB::beginTransaction();
            
            $unidade = Unidade::findOrFail($request->unidade_id);
            $files = $request->file('files') ?? [];
            $midiaTipos = $request->midia_tipos ?? [];
            $midiasReplace = $request->midia_replace ?? [];
            $midiasRemover = $request->midia_remover ?? [];
            $ambientesNaoPossui = $request->ambientes_nao_possui ?? [];
            
            // Detectar se é upload em lotes
            $isBatchUpload = in_array($request->get('is_batch_upload'), ['1', 1, 'true', true], true);

            // Log para debugging
            \Log::info('Upload de mídias iniciado', [
                'unidade_id' => $unidade->id,
                'files_count' => count($files),
                'is_batch_upload' => $isBatchUpload,
                'memory_usage' => memory_get_usage(true),
                'time_start' => now()
            ]);

            // Validação adaptada para lotes
            if (!$isBatchUpload) {
                // Upload normal: validar quantidade completa
                $this->validatePhotoQuantity($unidade, $files, $midiaTipos, $midiasRemover, $midiasReplace);
            } else {
                // Upload em lotes: validar apenas limites máximos e tipos obrigatórios
                $this->validatePhotoQuantityForBatch($unidade, $files, $midiaTipos, $midiasRemover, $midiasReplace);
            }

            // Processar exclusões de mídias (apenas no primeiro lote)
            if (!empty($midiasRemover)) {
                $this->processRemoveMedias($unidade, $midiasRemover);
            }

            // Processar dados de "não possui ambiente"
            $this->processarAmbientesNaoPossui($unidade, $ambientesNaoPossui);

            // Se não há arquivos para processar, verificar se é finalização
            if (empty($files)) {
                // Se é finalização de rascunho, fazer validação e finalizar
                if ($unidade->is_draft === true && !$isBatchUpload) {
                    $this->validateUnidadeForFinalization($unidade);
                    
                    $unidade->update([
                        'is_draft' => false,
                        'status' => 'pendente_avaliacao'
                    ]);
                    
                    DB::commit();
                    return response()->json([
                        'success' => true,
                        'message' => 'Cadastro finalizado com sucesso! Redirecionando...',
                        'redirect' => route('unidades.show', [
                            'team' => $unidade->team_id,
                            'unidade' => $unidade->id
                        ])
                    ]);
                }
                
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Alterações processadas com sucesso.',
                ]);
            }

            // Processar upload dos arquivos
            $uploadedCount = 0;
            $processedTypes = [];
            
            foreach ($files as $index => $file) {
                try {
                    $tipoId = $midiaTipos[$index];
                    $shouldReplace = isset($midiasReplace[$index]) && 
                        $this->processMidiaReplaceValue($midiasReplace[$index]);
                    
                    // Se for substituição, remover mídias existentes deste tipo (apenas uma vez por tipo)
                    if ($shouldReplace && !in_array($tipoId, $processedTypes)) {
                        $this->removeExistingMediaForType($unidade, $tipoId, $midiasRemover);
                        $processedTypes[] = $tipoId;
                    }
                    
                    // Fazer upload do arquivo
                    $this->uploadMediaFile($unidade, $file, $tipoId, $index);
                    $uploadedCount++;
                    
                    // Log de progresso a cada 10 arquivos
                    if ($uploadedCount % 10 === 0) {
                        \Log::info("Upload progresso: {$uploadedCount}/" . count($files) . " arquivos processados");
                    }
                    
                    // Limpeza de memória a cada 20 arquivos
                    if ($uploadedCount % 20 === 0) {
                        if (function_exists('gc_collect_cycles')) {
                            gc_collect_cycles();
                        }
                    }
                    
                } catch (\Exception $e) {
                    \Log::error("Erro no upload do arquivo {$index}: " . $e->getMessage());
                    throw $e;
                }
            }

            DB::commit();

            // Log final
            \Log::info('Upload de mídias concluído', [
                'unidade_id' => $unidade->id,
                'uploaded_count' => $uploadedCount,
                'memory_usage_final' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'time_end' => now()
            ]);

            // Verificar se é finalização (unidade em rascunho) - APENAS para uploads normais ou último lote
            $isFinalizing = $unidade->is_draft === true && !$isBatchUpload;
            
            if ($isFinalizing) {
                $this->validateUnidadeForFinalization($unidade);
                
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
                'message' => $isBatchUpload 
                    ? "Lote de {$uploadedCount} mídia(s) salva(s) com sucesso!"
                    : "Mídias salvas com sucesso!",
                'uploaded_count' => $uploadedCount,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Erro ao salvar mídias: ' . $e->getMessage(), [
                'unidade_id' => $request->unidade_id,
                'files_count' => count($request->file('files') ?? []),
                'memory_usage' => memory_get_usage(true),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validar quantidade de fotos para upload em lotes
     */
    private function validatePhotoQuantityForBatch($unidade, $files, $midiaTipos, $midiasRemover, $midiasReplace)
    {
        \Log::info('Validação para lote iniciada', [
            'files_count' => count($files),
            'midia_tipos' => $midiaTipos,
            'unidade_id' => $unidade->id
        ]);

        // Agrupar arquivos por tipo (apenas do lote atual)
        $filesByType = [];
        foreach ($files as $index => $file) {
            $tipoId = $midiaTipos[$index];
            if (!isset($filesByType[$tipoId])) {
                $filesByType[$tipoId] = 0;
            }
            $filesByType[$tipoId]++;
        }

        // Verificar apenas limites máximos para cada tipo no lote atual
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

            // Calcular total após este lote
            $finalCount = $willReplace ? $newCount : ($existingCount + $newCount);

            \Log::info("Validação lote - tipo {$midiaTipo->nome}", [
                'tipo_id' => $tipoId,
                'existing_count' => $existingCount,
                'new_count' => $newCount,
                'will_replace' => $willReplace,
                'final_count' => $finalCount
            ]);

            // APENAS validar limite máximo (3 fotos)
            if ($finalCount > 3) {
                throw new \Exception("O tipo '{$midiaTipo->nome}' pode ter no máximo 3 fotos. Após este lote ficará com: {$finalCount}");
            }
        }

        // NÃO validar tipos obrigatórios em lotes - será validado apenas na finalização
        \Log::info('Validação de lote concluída - tipos obrigatórios não verificados (será feito na finalização)');
    }

    /**
     * Validar quantidade de fotos por tipo (2-3)
     */
    private function validatePhotoQuantity($unidade, $files, $midiaTipos, $midiasRemover, $midiasReplace)
    {
        // Log para debug
        \Log::info('Validando quantidade de fotos (upload normal)', [
            'files_count' => count($files),
            'midia_tipos' => $midiaTipos,
            'unidade_id' => $unidade->id
        ]);

        // Agrupar arquivos por tipo
        $filesByType = [];
        foreach ($files as $index => $file) {
            $tipoId = $midiaTipos[$index];
            if (!isset($filesByType[$tipoId])) {
                $filesByType[$tipoId] = 0;
            }
            $filesByType[$tipoId]++;
        }

        // Log dos arquivos agrupados por tipo
        \Log::info('Arquivos agrupados por tipo', ['filesByType' => $filesByType]);

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

            // Log detalhado por tipo
            \Log::info("Validação do tipo {$midiaTipo->nome}", [
                'tipo_id' => $tipoId,
                'existing_count' => $existingCount,
                'new_count' => $newCount,
                'will_replace' => $willReplace,
                'final_count' => $finalCount
            ]);

            // Identificar categoria do tipo
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

        // Verificar se tipos obrigatórios têm pelo menos fotos suficientes (para upload normal)
        $tiposObrigatorios = [
            'foto_frente', 'foto_lateral_1', 'foto_lateral_2', 'foto_fundos', 
            'foto_medidor_agua', 'foto_medidor_energia'
        ];
        
        \Log::info('Verificando tipos obrigatórios', ['tipos' => $tiposObrigatorios]);
        
        foreach ($tiposObrigatorios as $tipoNome) {
            $tipoModel = MidiaTipo::where('nome', $tipoNome)->first();
            if (!$tipoModel) {
                \Log::warning("Tipo obrigatório não encontrado: {$tipoNome}");
                continue;
            }
            
            $tipoId = $tipoModel->id;
            
            // Verificar se o tipo tem arquivos novos sendo enviados
            $hasNewFiles = isset($filesByType[$tipoId]) && $filesByType[$tipoId] > 0;
            
            \Log::info("Verificando tipo obrigatório: {$tipoNome}", [
                'tipo_id' => $tipoId,
                'has_new_files' => $hasNewFiles,
                'new_files_count' => $filesByType[$tipoId] ?? 0
            ]);
            
            // Se não tem arquivos novos, verificar se tem existentes suficientes
            if (!$hasNewFiles) {
                $existingCount = Midia::whereHas('unidades', function($query) use ($unidade) {
                    $query->where('unidades.id', $unidade->id)
                        ->where('midia_unidade.nao_possui_ambiente', false);
                })->where('midia_tipo_id', $tipoId)
                ->whereNotIn('id', $midiasRemover)
                ->count();
                
                \Log::info("Tipo {$tipoNome} - fotos existentes: {$existingCount}");
                                
                // Só dar erro se não tem arquivos novos E não tem existentes suficientes
                if ($existingCount < 2) {
                    throw new \Exception("O tipo '$tipoNome' deve ter no mínimo 2 fotos. Atual: {$existingCount}");
                }
            }
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
        $tiposObrigatorios = [
            'foto_frente', 'foto_lateral_1', 'foto_lateral_2', 'foto_fundos', 
            'foto_medidor_agua', 'foto_medidor_energia'
        ];
        
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
            
            \Log::info("Validação final - Tipo {$tipoNome}: {$count} fotos encontradas");
            
            if ($count < 2) {
                throw new \Exception("O tipo '$tipoNome' deve ter no mínimo 2 fotos. Atual: {$count}");
            }
        }
    }

    /**
     * Processar remoção de mídias
     */
    private function processRemoveMedias($unidade, $midiasRemover)
    {
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

    /**
     * Remover mídias existentes de um tipo específico (para substituição)
     */
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
            $randomString = \Str::random(8);
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
            MidiaUnidade::create([
                'unidade_id' => $unidade->id,
                'midia_id' => $midia->id,
                'nao_possui_ambiente' => false,
            ]);
            
        } catch (\Exception $e) {
            \Log::error("Erro no upload do arquivo: " . $e->getMessage(), [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'tipo_id' => $tipoId,
                'unidade_id' => $unidade->id
            ]);
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

    /**
     * Método para atualização de mídias
     */
    public function update(Request $request, $unidade_id)
    {
        // Atualizar o unidade_id no request
        $request->merge(['unidade_id' => $unidade_id]);
        
        return $this->store($request);
    }

    /**
     * Excluir uma mídia específica
     */
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

    /**
     * Buscar mídias de uma unidade (para atualização via AJAX)
     */
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