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
            ['nome' => 'recepcao', 'descricao' => 'Foto da recepção'],
            ['nome' => 'wc_publico', 'descricao' => 'Foto do banheiro público'],
            ['nome' => 'gabinetes', 'descricao' => 'Foto dos gabinetes'],
            ['nome' => 'cartorio', 'descricao' => 'Foto do cartório'],
            ['nome' => 'sala_oitiva', 'descricao' => 'Foto da sala de oitiva'],
            ['nome' => 'wc_servidores', 'descricao' => 'Foto do banheiro dos servidores'],
            ['nome' => 'alojamento_masculino', 'descricao' => 'Foto do alojamento masculino'],
            ['nome' => 'alojamento_feminino', 'descricao' => 'Foto do alojamento feminino'],
            ['nome' => 'xadrez_masculino', 'descricao' => 'Foto do xadrez masculino'],
            ['nome' => 'xadrez_feminino', 'descricao' => 'Foto do xadrez feminino'],
            ['nome' => 'sala_identificacao', 'descricao' => 'Foto da sala de identificação'],
            ['nome' => 'cozinha', 'descricao' => 'Foto da cozinha'],
            ['nome' => 'area_servico', 'descricao' => 'Foto da área de serviço'],
            ['nome' => 'dispensa', 'descricao' => 'Foto da dispensa'],
            ['nome' => 'deposito_apreensao', 'descricao' => 'Foto do depósito de apreensão'],

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