<?php

namespace Database\Seeders;

use App\Models\MidiaTipo;
use Illuminate\Database\Seeder;

class MidiaTiposSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tipos = [
            ['nome' => 'foto_frente', 'descricao' => 'Foto da frente da unidade'],
            ['nome' => 'foto_lateral_1', 'descricao' => 'Foto da lateral 1'],
            ['nome' => 'foto_lateral_2', 'descricao' => 'Foto da lateral 2'],
            ['nome' => 'foto_fundos', 'descricao' => 'Foto dos fundos'],
            ['nome' => 'foto_medidor_agua', 'descricao' => 'Foto do medidor de água'],
            ['nome' => 'foto_medidor_energia', 'descricao' => 'Foto do medidor de energia'],

            ['nome' => 'recepção', 'descricao' => 'Foto da recepção'],
            ['nome' => 'sala_oitiva', 'descricao' => 'Foto da sala de oitiva'],
            ['nome' => 'sala_boletim_de_ocorrência', 'descricao' => 'Foto da sala de Boletim de Ocorrência'],
            ['nome' => 'gabinete_01', 'descricao' => 'Foto do gabinete 01'],
            ['nome' => 'gabinete_02', 'descricao' => 'Foto do gabinete 02'],
            ['nome' => 'cartório_01', 'descricao' => 'Foto do cartório 01'],
            ['nome' => 'cartório_02', 'descricao' => 'Foto do cartório 02'],
            ['nome' => 'sala_de_agentes', 'descricao' => 'Foto da sala de Agentes'],
            ['nome' => 'wc_público_masculino', 'descricao' => 'Foto do banheiro público masculino'],
            ['nome' => 'wc_público_feminino', 'descricao' => 'Foto do banheiro público feminino'],
            ['nome' => 'wc_servidores_masculino', 'descricao' => 'Foto do banheiro dos servidores masculino'],
            ['nome' => 'wc_servidores_feminino', 'descricao' => 'Foto do banheiro dos servidores feminino'],
            ['nome' => 'alojamento_masculino', 'descricao' => 'Foto do alojamento masculino'],
            ['nome' => 'alojamento_feminino', 'descricao' => 'Foto do alojamento feminino'],
            ['nome' => 'xadrez_masculino_01', 'descricao' => 'Foto do xadrez masculino 01'],
            ['nome' => 'xadrez_masculino_02', 'descricao' => 'Foto do xadrez masculino 02'],
            ['nome' => 'xadrez_masculino_03', 'descricao' => 'Foto do xadrez masculino 03'],
            ['nome' => 'xadrez_feminino_01', 'descricao' => 'Foto do xadrez feminino 01'],
            ['nome' => 'xadrez_feminino_02', 'descricao' => 'Foto do xadrez feminino 02'],
            ['nome' => 'xadrez_feminino_03', 'descricao' => 'Foto do xadrez feminino 03'],
            ['nome' => 'parlatório', 'descricao' => 'Foto do parlatório'],
            ['nome' => 'sala_identificação', 'descricao' => 'Foto da sala de identificação'],
            ['nome' => 'cozinha', 'descricao' => 'Foto da cozinha'],
            ['nome' => 'copa', 'descricao' => 'Foto da copa'],
            ['nome' => 'área_de_serviço', 'descricao' => 'Foto da área de serviço'],
            ['nome' => 'dispensa', 'descricao' => 'Foto da dispensa'],
            ['nome' => 'depósito_apreensão', 'descricao' => 'Foto do depósito de apreensão'],
            ['nome' => 'garagem', 'descricao' => 'Foto da garagem'],

            // Acessibilidades
            ['nome' => 'rampa_acesso', 'descricao' => 'Foto da rampa de acesso'],
            ['nome' => 'corrimao', 'descricao' => 'Foto do corrimão'],
            ['nome' => 'piso_tatil', 'descricao' => 'Foto do piso tátil'],
            ['nome' => 'banheiro_adaptado', 'descricao' => 'Foto do banheiro adaptado'],
            ['nome' => 'elevador', 'descricao' => 'Foto do elevador'],
            ['nome' => 'sinalizacao_braile', 'descricao' => 'Foto da sinalização em braile'],
        ];

        foreach ($tipos as $tipo) {
            MidiaTipo::firstOrCreate(
                ['nome' => $tipo['nome']], // busca por nome único
                [
                    'nome' => $tipo['nome'],
                    'descricao' => $tipo['descricao'],
                    'ativo' => true
                ]
            );
        }
    }
}