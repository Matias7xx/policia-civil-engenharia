<?php

namespace App\Http\Controllers;

use App\Models\Midia;
use App\Models\Unidade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MidiaController extends Controller
{
    public function store(Request $request)
{
    $request->validate([
        'unidade_id' => 'required|exists:unidades,id',
        'files' => 'required|array',
        'files.*' => 'required|file|mimes:jpg,jpeg,png,mp4|max:10240',
        'midia_tipos' => 'required|array',
        'midia_tipos.*' => 'required|exists:midia_tipos,id',
    ], [
        'files.*.mimes' => 'Os arquivos devem ser do tipo JPG, PNG ou MP4.',
        'files.*.max' => 'Os arquivos não podem exceder 10MB.',
    ]);

    try {
        $unidade = Unidade::findOrFail($request->unidade_id);
        $files = $request->file('files');
        $midiaTipos = $request->midia_tipos;

        if (empty($files)) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum arquivo foi enviado.',
            ], 422);
        }

        $requiredMidiaTipos = [
            'foto_frente',
            'foto_lateral_1',
            'foto_lateral_2',
            'foto_fundos',
            'foto_medidor_agua',
            'foto_medidor_energia',
        ];
        
        $uploadedMidiaTipos = \App\Models\MidiaTipo::whereIn('id', array_values($midiaTipos))
            ->pluck('nome')
            ->toArray();
        $missingRequired = array_diff($requiredMidiaTipos, $uploadedMidiaTipos);
        
        if (!empty($missingRequired)) {
            return response()->json([
                'success' => false,
                'message' => "Os seguintes tipos de mídia são obrigatórios: " . implode(', ', $missingRequired),
            ], 422);
        }

        foreach ($files as $index => $file) {
            $path = $file->store('midias', 'public');
            $midia = Midia::create([
                'unidade_id' => $unidade->id,
                'midia_tipo_id' => $midiaTipos[$index],
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'tamanho' => $file->getSize(),
            ]);

            // Criar relacionamento na tabela midia_unidade
            \App\Models\MidiaUnidade::create([
                'unidade_id' => $unidade->id,
                'midia_id' => $midia->id,
            ]);
        }

        \Log::info('Tentativa de salvar mídias', [
            'unidade_id' => $request->unidade_id,
            'file_count' => count($request->file('files')),
            'midia_tipos' => $request->midia_tipos,
        ]);

        return response()->json(['success' => true, 'message' => 'Mídias salvas com sucesso.']);
    } catch (\Exception $e) {
        \Log::error('Erro ao salvar mídias: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Erro interno ao salvar as mídias: ' . $e->getMessage(),
        ], 500);
    }
}
}