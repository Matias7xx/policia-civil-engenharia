<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
            ['nome' => 'xadrez', 'descricao' => 'Foto da cela/xadrez'],
            ['nome' => 'sala_identificacao', 'descricao' => 'Foto da sala de identificação'],
            ['nome' => 'cozinha', 'descricao' => 'Foto da cozinha'],
            ['nome' => 'area_servico', 'descricao' => 'Foto da área de serviço'],
            ['nome' => 'dispensa', 'descricao' => 'Foto da dispensa'],
            ['nome' => 'deposito_apreensao', 'descricao' => 'Foto do depósito de apreensão'],
        ];

        foreach ($tipos as $tipo) {
            DB::table('midia_tipos')->insert([
                'nome' => $tipo['nome'],
                'descricao' => $tipo['descricao'],
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}