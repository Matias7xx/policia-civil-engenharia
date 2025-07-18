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
            foreach ($files as $index => $file) {
                // Verificar se este tipo de mídia deve substituir os existentes
                $tipoId = $midiaTipos[$index];
                $shouldReplace = isset($midiasReplace[$index]) && 
                 $this->processMidiaReplaceValue($midiasReplace[$index]);
                
                // Se for substituição, remover mídias existentes deste tipo
                if ($shouldReplace) {
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
                
                // Obter o nome do tipo de mídia para o arquivo
                $midiaTipo = MidiaTipo::find($tipoId);
                $nomeArquivo = $midiaTipo ? $midiaTipo->nome . '.' . $file->getClientOriginalExtension() : $file->getClientOriginalName();
                
                // Salvar o arquivo no MinIO
                $path = StorageHelper::salvarFoto($unidade, $file, $nomeArquivo);
                
                $midia = Midia::create([
                    'midia_tipo_id' => $tipoId,
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'tamanho' => $file->getSize(),
                ]);

                // Criar relacionamento na tabela midia_unidade
                MidiaUnidade::create([
                    'unidade_id' => $unidade->id,
                    'midia_id' => $midia->id,
                    'nao_possui_ambiente' => false,
                ]);
            }

            // Atualizar status se unidade estiver reprovada
            if ($unidade->status === 'reprovada') {
                $unidade->update(['status' => 'pendente_avaliacao']);
            }

            return response()->json(['success' => true, 'message' => 'Mídias salvas com sucesso.']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro interno ao salvar as mídias: ' . $e->getMessage(),
            ], 500);
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
}