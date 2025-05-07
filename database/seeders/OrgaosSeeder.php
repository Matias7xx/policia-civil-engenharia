<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Orgao;

class OrgaosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orgaos = [
            ['nome' => 'Polícia Militar do Estado da Paraíba', 'status' => 'ativo'],
            ['nome' => 'Corpo de Bombeiros Militar do Estado da Paraíba', 'status' => 'ativo'],
            ['nome' => 'Ministério Público do Estado da Paraíba', 'status' => 'ativo'],
            ['nome' => 'Secretaria de Estado da Fazenda - SEFAZ-PB', 'status' => 'ativo'],
            ['nome' => 'Procuradoria Geral do Estado da Paraíba', 'status' => 'ativo'],
            ['nome' => 'Departamento Estadual de Trânsito - DETRAN-PB', 'status' => 'ativo'],
            ['nome' => 'Conselho Tutelar', 'status' => 'ativo'],
            ['nome' => 'Tribunal de Justiça do Estado da Paraíba', 'status' => 'ativo'],
        ];

        foreach ($orgaos as $orgao) {
            Orgao::firstOrCreate(['nome' => $orgao['nome']], $orgao);
        }
    }
}