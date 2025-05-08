<?php

namespace App\Http\Controllers;

use App\Models\Midia;
use App\Models\Unidade;
use App\Models\MidiaUnidade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            'files' => 'required|array',
            'files.*' => 'required|file|mimes:jpg,jpeg,png|max:10240',
            'midia_tipos' => 'required|array',
            'midia_tipos.*' => 'required|exists:midia_tipos,id',
            'midia_replace' => 'array',
            'midia_replace.*' => 'nullable|boolean',
            'midia_remover' => 'array',
            'midia_remover.*' => 'exists:midias,id',
        ], [
            'files.*.mimes' => 'Os arquivos devem ser do tipo JPG ou PNG.',
            'files.*.max' => 'Os arquivos não podem exceder 10MB.',
            'files.*required' => 'Preencha todos os campos marcados com "*"'
        ]);

        try {
            $unidade = Unidade::findOrFail($request->unidade_id);
            $files = $request->file('files');
            $midiaTipos = $request->midia_tipos;
            $midiasReplace = $request->midia_replace ?? [];
            $midiasRemover = $request->midia_remover ?? [];

            // Processar exclusões de mídias
            if (!empty($midiasRemover)) {
                foreach ($midiasRemover as $midiaId) {
                    $midia = Midia::find($midiaId);
                    if ($midia) {
                        // Verificar se a mídia pertence à unidade
                        $pertenceUnidade = MidiaUnidade::where('unidade_id', $unidade->id)
                            ->where('midia_id', $midia->id)
                            ->exists();
                        
                        if ($pertenceUnidade) {
                            // Remover arquivo físico
                            if (Storage::disk('public')->exists($midia->path)) {
                                Storage::disk('public')->delete($midia->path);
                            }
                            
                            // Remover relacionamento
                            MidiaUnidade::where('midia_id', $midia->id)->delete();
                            
                            // Remover registro
                            $midia->delete();
                        }
                    }
                }
            }

            if (empty($files)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Nenhum arquivo novo foi enviado, mas as exclusões foram processadas.',
                ]);
            }

             // Criar o slug do nome da unidade
             $unidadeSlug = Str::slug($unidade->nome, '-');
             // Combinar ID e slug para o caminho
             $directory = "midias/{$unidade->id}-{$unidadeSlug}";
 
             foreach ($files as $index => $file) {
                 // Verificar se este tipo de mídia deve substituir os existentes
                 $tipoId = $midiaTipos[$index];
                 $shouldReplace = isset($midiasReplace[$index]) && 
                 $this->processMidiaReplaceValue($midiasReplace[$index]);
                 
                 // Se for substituição, remover mídias existentes deste tipo
                 if ($shouldReplace) {
                     $midiasExistentes = Midia::whereHas('unidades', function($query) use ($unidade, $tipoId) {
                         $query->where('unidades.id', $unidade->id);
                     })->where('midia_tipo_id', $tipoId)
                       ->whereNotIn('id', $midiasRemover)
                       ->get();
                     
                     foreach ($midiasExistentes as $midiaExistente) {
                         // Remover arquivo físico
                         if (Storage::disk('public')->exists($midiaExistente->path)) {
                             Storage::disk('public')->delete($midiaExistente->path);
                         }
                         
                         // Remover relacionamento
                         MidiaUnidade::where('midia_id', $midiaExistente->id)->delete();
                         
                         // Remover registro
                         $midiaExistente->delete();
                     }
                 }
                 
                 // Salvar o arquivo no diretório personalizado
                 $path = $file->store($directory, 'public');
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
 
     // Método para atualização de mídias (reutiliza a lógica do store mas com adaptações para edição)
     public function update(Request $request, $unidade_id)
     {
         // Garante que a unidade_id na rota seja usada
         $request->merge(['unidade_id' => $unidade_id]);
         
         return $this->store($request);
     }
 
     // Método para excluir uma mídia específica
     public function destroy($id)
     {
         try {
             $midia = Midia::findOrFail($id);
             
             // Remover arquivo físico
             if (Storage::disk('public')->exists($midia->path)) {
                 Storage::disk('public')->delete($midia->path);
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
}