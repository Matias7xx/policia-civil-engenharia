<?php

namespace App\Http\Controllers;

use App\Models\MidiaTipo;
use Illuminate\Http\Request;

class MidiaTipoController extends Controller
{
    public function ativos()
    {
        $midiaTipos = MidiaTipo::where('ativo', true)->get(['id', 'nome']);
        return response()->json($midiaTipos);
    }
}
