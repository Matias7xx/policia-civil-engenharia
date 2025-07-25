<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Team;
use App\Models\Unidade;
use Illuminate\Support\Facades\DB;

class UnidadeSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $unidades = [
                'Suporte de Sistemas',
                '2 Delegacia Distrital de Campina Grande',
                'Delegacia Especializada de Combate a Circulacao e Comercializacao Ilegal de Armas de Fogo, Municoes e Explosivos',
                'Delegacia Geral Adjunta',
                '6 Delegacia Distrital de Campina Grande',
                'Unidade de Recursos Humanos',
                '1 Delegacia Distrital de Campina Grande',
                'Delegacia Especializada de Atendimento a Mulher de Queimadas',
                'Delegacia Especializada do Meio Ambiente de Campina Grande',
                'Delegacia Especializada de Atendimento a Mulher de Campina Grande',
                'Delegacia Especializada da Infancia e Juventude de Campina Grande',
                'Delegacia Especializada de Repressao Aos Crimes Contra a Infancia e a Juventude de Campina Grande',
                'Delegacia Especializada de Crimes Contra o Patrimonio de Campina Grande',
                'Delegacia Especializada de Repressao a Entorpecentes de Campina Grande',
                'Delegacia Especializada de Crimes Contra Pessoa de Campina Grande',
                'Delegacia do Municipio de Massaranduba',
                'Delegacia do Municipio de Lagoa Seca',
                '2 Superintendencia Regional de Policia Civil',
                '10 Delegacia Seccional de Policia Civil',
                '1 Delegacia Seccional de Policia Civil',
                '22 Delegacia Seccional de Policia Civil',
                '3 Delegacia Distrital de Campina Grande',
                '4 Delegacia Distrital de Campina Grande',
                '5 Delegacia Distrital de Campina Grande',
                '7 Delegacia Distrital de Campina Grande',
                'Delegacia Especializada de Defraudacoes e Falsificacoes de Campina Grande',
                'Delegacia do Municipio de Boa Vista',
                '12 Delegacia Seccional de Policia Civil',
                '11 Delegacia Seccional de Policia Civil',
                'Delegacia Especializada de Acidentes de Veiculos de Campina Grande',
                'Delegacia Especializada do Idoso de Campina Grande',
                '2 Delegacia Seccional de Policia Civil',
                'Delegacia Especializada da Ordem  Economica de Guarabira',
                'Delegacia Especializada de Roubos e Furtos Veiculos de Campina Grande',
                'Delegacia Especializada da Ordem  Economica de Campina Grande',
                'Delegacia do Municipio de Umbuzeiro',
                'Delegacia do Municipio de Santa Cecilia',
                'Delegacia do Municipio de Natuba',
                'Delegacia do Municipio de Alcantil',
                'Delegacia de Comarca de Aroeiras',
                'Delegacia do Municipio de Barra de Santana',
                'Delegacia do Municipio de Gado Bravo',
                'Delegacia de Comarca de Boqueirao',
                'Delegacia do Municipio de Barra de Sao Miguel',
                'Delegacia de Comarca de Cabaceiras',
                'Delegacia do Municipio de Riacho de Santo Antonio',
                'Delegacia do Municipio de Sao Domingos do Cariri',
                'Delegacia do Municipio de Caturite',
                'Delegacia do Municipio de Fagundes',
                'Delegacia de Comarca de Queimadas',
                'Delegacia de Comarca de Soledade',
                'Delegacia do Municipio de Baraunas',
                'Delegacia de Comarca de Barra de Santa Rosa',
                'Delegacia de Comarca de Cuite',
                'Delegacia do Municipio de Damiao',
                'Delegacia do Municipio de Frei Martinho',
                'Delegacia do Municipio de Cubati',
                'Delegacia do Municipio de Nova Floresta',
                'Delegacia do Municipio de Nova Palmeira',
                'Delegacia do Municipio de Pedra Lavrada',
                'Delegacia de Comarca de Picui',
                'Delegacia do Municipio de Sossego',
                'Delegacia do Municipio de Sao Vicente Serido',
                '1 Superintendencia Regional de Policia Civil',
                '3 Superintendencia Regional de Policia Civil',
                'Delegacia do Municipio de Riachao de Bacamarte',
                'Delegacia de Comarca de Esperanca',
                'Delegacia do Municipio de Areial',
                'Delegacia do Municipio de Montadas',
                '3 Delegacia Seccional de Policia Civil',
                '7 Delegacia Distrital de Cabedelo',
                '1 Delegacia Distrital da Capital',
                '4 Delegacia Distrital da Capital',
                '5 Delegacia Distrital de Bayeux',
                'Delegacia de Comarca de Cruz Espirito Santo',
                'Delegacia de Comarca de Lucena',
                'Delegacia de Comarca de Mari',
                '6 Delegacia Distrital de Santa Rita',
                'Delegacia do Municipio de Riachao do Poco',
                'Delegacia de Comarca de Sape',
                'Delegacia do Municipio de Sobrado',
                'Delegacia de Comarca de Alhandra',
                'Delegacia de Comarca de Caapora',
                'Delegacia do Municipio do Conde',
                'Delegacia de Comarca de Pedras de Fogo',
                'Delegacia do Municipio de Pitimbu',
                'Delegacia do Municipio de Baia da Traicao',
                'Delegacia do Municipio de Capim',
                'Delegacia do Municipio de Cuite de Mamanguape',
                'Delegacia do Municipio de Curral de Cima',
                'Delegacia do Municipio de Itapororoca',
                'Delegacia de Comarca de Jacarau',
                'Delegacia do Municipio de Lagoa de Dentro',
                'Delegacia de Comarca de Mamanguape',
                'Delegacia do Municipio de Marcacao',
                'Delegacia do Municipio de Mataraca',
                'Delegacia do Municipio de Pedro Regis',
                'Delegacia de Comarca de Rio Tinto',
                'Delegacia de Comarca de Alagoinha',
                'Delegacia do Municipio de Alagoa Grande',
                'Delegacia de Comarca de Aracagi',
                'Delegacia de Comarca de Belem',
                'Delegacia do Municipio de Caicara',
                'Delegacia do Municipio de Cuitegi',
                'Delegacia do Municipio de Duas Estradas',
                '1 Delegacia Distrital de Guarabira',
                'Delegacia do Municipio de Logradouro',
                'Delegacia de Comarca de Piloes',
                'Delegacia do Municipio de Piloezinhos',
                'Delegacia de Comarca de Pirpirituba',
                'Delegacia do Municipio de Serra da Raiz',
                'Delegacia do Municipio de Sertaozinho',
                'Delegacia do Municipio de Mulungu',
                'Delegacia do Municipio de Caldas Brandao',
                'Delegacia de Comarca de Gurinhem',
                'Delegacia de Comarca de Inga',
                'Delegacia do Municipio de Itabaiana',
                'Delegacia do Municipio de Itatuba',
                'Delegacia do Municipio de Juripiranga',
                'Delegacia do Municipio de Mogeiro',
                'Delegacia de Comarca de Pilar',
                'Delegacia do Municipio de Salgado de Sao Felix',
                'Delegacia do Municipio de Sao Jose dos Ramos',
                'Delegacia do Municipio de Sao Miguel de Taipu',
                'Delegacia do Municipio de Serra Redonda',
                'Delegacia de Comarca de Alagoa Nova',
                'Delegacia do Municipio de Algodao de Jandaira',
                'Delegacia de Comarca de Areia',
                'Delegacia do Municipio de Juazeirinho',
                'Delegacia do Municipio de Matinhas',
                'Delegacia do Municipio de Olivedos',
                'Delegacia da Comarca de Pocinhos',
                'Delegacia do Municipio de Puxinana',
                'Delegacia de Comarca de Remigio',
                'Delegacia do Municipio de Sao Sebastiao Lagoa de Roca',
                'Delegacia do Municipio de Santo Andre',
                'Delegacia do Municipio de Tenorio',
                'Delegacia do Municipio de Amparo',
                'Delegacia do Municipio de Camalau',
                'Delegacia do Municipio de Caraubas',
                'Delegacia do Municipio do Congo',
                'Delegacia do Municipio de Coxixola',
                'Delegacia do Municipio de Gurjao',
                'Delegacia de Comarca de Monteiro',
                'Delegacia do Municipio de Ouro Velho',
                'Delegacia do Municipio de Parari',
                'Delegacia de Comarca de Prata',
                'Delegacia de Comarca de Sao Joao do Cariri',
                'Delegacia do Municipio de Sao Joao do Tigre',
                'Delegacia do Municipio de Sao Jose dos Cordeiros',
                'Delegacia do Municipio de Sao Sebastiao do Umbuzeiro',
                'Delegacia de Comarca de Serra Branca',
                'Delegacia de Comarca de Sume',
                'Delegacia do Municipio de Zabele',
                'Delegacia do Municipio de Areia de Baraunas',
                'Delegacia do Municipio de Assuncao',
                'Delegacia do Municipio de Cacimba de Areia',
                'Delegacia do Municipio de Cacimbas',
                'Delegacia do Municipio de Condado',
                'Delegacia do Municipio de Desterro',
                'Delegacia do Municipio de Livramento',
                'Delegacia do Municipio de Junco do Serido',
                'Delegacia do Municipio de Mae Dagua',
                'Delegacia de Comarca de Malta',
                'Delegacia do Municipio de Matureia',
                'Delegacia do Municipio de Passagem',
                '1 Delegacia Distrital de Patos',
                'Delegacia do Municipio de Quixaba',
                'Delegacia do Municipio de Salgadinho',
                'Delegacia de Comarca de Santa Luzia',
                'Delegacia do Municipio de Santa Terezinha',
                'Delegacia do Municipio de Sao Jose de Espinharas',
                'Delegacia do Municipio de Sao Jose do Sabugi',
                'Delegacia de Comarca de Sao Mamede',
                'Delegacia de Comarca de Taperoa',
                'Delegacia de Comarca de Teixeira',
                'Delegacia do Municipio de Varzea',
                'Delegacia do Municipio de Vista Serrana',
                'Delegacia de Comarca de Agua Branca',
                'Delegacia do Municipio de Imaculada',
                'Delegacia do Municipio de Juru',
                'Delegacia do Municipio de Manaira',
                'Delegacia de Comarca de Princesa Isabel',
                'Delegacia do Municipio de Tavares',
                'Delegacia do Municipio de Aguiar',
                'Delegacia do Municipio de Boa Ventura',
                'Delegacia do Municipio de Catingueira',
                'Delegacia de Comarca de Conceicao',
                'Delegacia de Comarca de Coremas',
                'Delegacia do Municipio de Curral Velho',
                'Delegacia do Municipio de Diamante',
                'Delegacia do Municipio de Emas',
                'Delegacia do Municipio de Ibiara',
                'Delegacia do Municipio de Igaracy',
                'Delegacia de Comarca de Itaporanga',
                'Delegacia do Municipio de Nova Olinda',
                'Delegacia do Municipio de Olho Dagua',
                'Delegacia do Municipio de Pedra Branca',
                'Delegacia de Comarca de Pianco',
                'Delegacia do Municipio de Santa Ines',
                'Delegacia do Municipio de Santana de Mangueira',
                'Delegacia do Municipio de Santana dos Garrotes',
                'Delegacia do Municipio de Sao Jose de Caiana',
                'Delegacia do Municipio de Serra Grande',
                'Delegacia do Municipio de Belem do Brejo do Cruz',
                'Delegacia do Municipio de Bom Sucesso',
                'Delegacia do Municipio de Brejo do Cruz',
                'Delegacia do Municipio de Brejo dos Santos',
                'Delegacia de Comarca de Catole do Rocha',
                'Delegacia do Municipio de Jerico',
                'Delegacia do Municipio de Lagoa',
                'Delegacia do Municipio de Mato Grosso',
                'Delegacia de Comarca de Paulista',
                'Delegacia do Municipio de Riacho dos Cavalos',
                'Delegacia de Comarca de Sao Bento',
                'Delegacia do Municipio de Sao Jose do Brejo do Cruz',
                'Delegacia do Municipio de Aparecida',
                'Delegacia do Municipio de Cajazeirinhas',
                'Delegacia do Municipio de Lastro',
                'Delegacia do Municipio de Marizopolis',
                'Delegacia do Municipio de Nazarezinho',
                '1  Delegacia Distrital de Pombal',
                'Delegacia do Municipio de Santa Cruz',
                'Delegacia do Municipio de Sao Bentinho',
                'Delegacia do Municipio de Sao Domingos de Pombal',
                'Delegacia do Municipio de Sao Francisco',
                'Delegacia do Municipio de Sao Jose de Lagoa Tapada',
                '1 Delegacia Distrital de Sousa',
                'Delegacia do Municipio de Vieiropolis',
                'Delegacia do Municipio de Bernardino Batista',
                'Delegacia do Municipio de Bom Jesus',
                'Delegacia do Municipio de Bonito de Santa Fe',
                'Delegacia do Municipio de Cachoeira dos Indios',
                '1 Delegacia Distrital de Cajazeiras',
                'Delegacia do Municipio de Carrapateira',
                'Delegacia do Municipio de Joca Claudino',
                'Delegacia do Municipio de Monte Horebe',
                'Delegacia do Municipio de Poco Dantas',
                'Delegacia do Municipio de Poco de Jose de Moura',
                'Delegacia do Municipio de Santa Helena',
                'Delegacia de Comarca de Sao Joao do Rio do Peixe',
                'Delegacia do Municipio de Sao Jose de Piranhas',
                'Delegacia do Municipio de Triunfo',
                'Delegacia de Comarca de Uirauna',
                'Delegacia de Comarca de Arara',
                'Delegacia de Comarca de Araruna',
                'Delegacia de Repressao ao Crime Organizado',
                'Delegacia de Comarca de Bananeiras',
                'Delegacia do Municipio de Borborema',
                'Delegacia de Comarca de Cacimba de Dentro',
                'Delegacia do Municipio de Casserengue',
                'Delegacia do Municipio de Dona Ines',
                'Delegacia do Municipio de Riachao',
                'Delegacia de Comarca de Serraria',
                'Delegacia de Comarca de Solanea',
                'Delegacia do Municipio de Tacima',
                'Coordenacao das Delegacias Especializadas da Mulher',
                '5 Delegacia Seccional de Policia Civil',
                '6 Delegacia Seccional de Policia Civil',
                '7 Delegacia Seccional de Policia Civil',
                '8 Delegacia Seccional de Policia Civil',
                '9 Delegacia Seccional de Policia Civil',
                '13 Delegacia Seccional de Policia Civil',
                '14 Delegacia Seccional de Policia Civil',
                '21 Delegacia Seccional de Policia Civil',
                '15 Delegacia Seccional de Policia Civil',
                '16 Delegacia Seccional de Policia Civil',
                '17 Delegacia Seccional de Policia Civil',
                '18 Delegacia Seccional de Policia Civil',
                '19 Delegacia Seccional de Policia Civil',
                '20 Delegacia Seccional de Policia Civil',
                '4 Delegacia Seccional de Policia Civil',
                'Delegacia Especializada de Crimes Contra Pessoa da Capital',
                'Delegacia Especializada de Crimes Contra o Patrimonio da Capital',
                'Delegacia Especializada de Acidentes de Veiculos da Capital',
                'Delegacia Especializada de Defraudacoes e Falsificacoes da Capital',
                'Delegacia Especializada da Infancia e Juventude da Capital',
                'Delegacia Especializada de Repressao Aos Crimes Contra Infancia e a Juventude da Capital',
                'Delegacia Especializada de Repressao a Entorpecentes da Capital',
                'Delegacia Especializada do Meio Ambiente da Capital',
                'Delegacia Especializada do Idoso da Capital',
                'Delegacia Especializada de Crimes Homofobicos da Capital',
                'Delegacia Especializada de Crimes Contra a Ordem Tributaria da Capital',
                'Delegacia Especializada de Atendimento Ao Turista da Capital',
                'Delegacia Especializada de Protecao e Defesa do Consumidor da Capital',
                'Central de Flagrantes de Joao Pessoa',
                'Delegacia Especializada da Infancia e Juventude Cajazeiras',
                'Delegacia Especializada da Ordem  Economica de Cajazeiras',
                'Delegacia Especializada da Ordem  Economica de Itaporanga',
                'Delegacia Especializada da Ordem  Economica de Patos',
                'Delegacia Especializada de Homicidios de Patos',
                'Delegacia Especializada da Ordem  Economica de Catole do Rocha',
                '2 Delegacia Distrital da Capital',
                '3 Delegacia Distrital da Capital',
                '10 Delegacia Distrital da Capital',
                '12 Delegacia Distrital da Capital',
                '8 Delegacia Distrital da Capital',
                '9 Delegacia Distrital da Capital',
                '11 Delegacia Distrital da Capital',
                '2 Delegacia Distrital de Patos',
                'Delegacia do Municipio de Sao Jose do Bonfim',
                '14 Delegacia Distrital de Santa Rita',
                'Delegacia do Municipio de Sao Jose de Princesa',
                '2 Delegacia Distrital de Guarabira',
                '3 Delegacia Distrital de Guarabira',
                '2  Delegacia Distrital de Pombal',
                '2 Delegacia Distrital de Sousa',
                '2 Delegacia Distrital de Cajazeiras',
                'Delegacia Geral',
                'Academia de Ensino da Policia Civil',
                'Grupo de Operacoes Especiais',
                'Unidade de Inteligencia da Policia Civil',
                'Delegacia Especializada de Roubos e Furtos Veiculos e cargas da Capital',
                'Delegacia Especializada de Atendimento a Mulher de Bayeux',
                'Delegacia Especializada de Atendimento a Mulher de Cabedelo',
                'Delegacia Especializada de Atendimento a Mulher de Cajazeiras',
                'Delegacia Especializada de Atendimento a Mulher de Guarabira',
                'Delegacia Especializada de Atendimento a Mulher de Joao Pessoa Norte',
                'Delegacia Especializada de Atendimento a Mulher de Joao Pessoa Sul',
                'Delegacia Especializada de Atendimento a Mulher de Mamanguape',
                'Delegacia Especializada de Atendimento a Mulher de Monteiro',
                'Delegacia Especializada de Atendimento a Mulher de Patos',
                'Delegacia Especializada de Atendimento a Mulher de Picui',
                'Delegacia Especializada de Atendimento a Mulher de Santa Rita',
                'Delegacia Especializada de Atendimento a Mulher de Sousa',
                'Delegacia de Combate a Corrupcao',
                'Delegacia Especializada de Roubos e Furtos de Patos',
                'Delegacia de Roubos e Furtos de Patos',
                'Coordenacao do Plantao de Patos',
                'Delegacia da Ordem Economica de Catole do Rocha',
                'Delegacia de Roubos e Furtos',
                'Coordenacao de Transporte',
                'Instituto de Policia Cientifica - Direcao Geral',
                '1 Superintendencia do IPC',
                'Nucleo de Criminalistica - Campina Grande',
                'Nucleo de Criminalistica - Joao Pessoa',
                'Nucleo de Criminalistica - Cajazeiras',
                'Nucleo de Criminalistica - Guarabira',
                'Nucleo de Criminalistica - Patos',
                'Nucleo de Identificacao Civil e Criminal - Joao Pessoa',
                'Nucleo de Medicina e Odontologia Legal - Campina Grande',
                'Nucleo de Medicina e Odontologia Legal - Joao Pessoa',
                'Nucleo de Medicina e Odontologia Legal - Cajazeiras',
                'Nucleo de Medicina e Odontologia Legal - Guarabira',
                'Nucleo de Medicina e Odontologia Legal - Patos',
                'Nucleo de Laboratorio Forense - Campina Grande',
                'Nucleo de Laboratorio Forense - Joao Pessoa',
                'Nucleo de Laboratorio Forense - Guarabira',
                'Nucleo de Laboratorio Forense - Patos',
                'Subcoordenacao DEAM Joao Pessoa',
                'Subcoordenacao DEAM Campina Grande',
                'Subcoordenacao DEAM Patos',
                'Delegacia do Municipio de Juarez Tavora',
                'Delegacia Especializada de Crimes Ciberneticos',
                '2 Superintendencia do IPC',
                'NECRIM - Nucleo de Praticas Restaurativas',
                '4 Superintendencia Regional de Policia Civil',
                '23 Delegacia Seccional de Policia Civil',
                '24 Delegacia Seccional de Policia Civil',
                'Central de Flagrantes de Campina Grande',
                'Unidade de Administracao',
                'Gerencia de Transportes',
                'Gerencia de Armamentos',
                'Gerencia de Logistica',
                'Diretoria de Administracao Geral',
                'Gerencia de Arquitetura e Engenharia',
                'Gerencia de Recursos Imobiliarios',
                'Gerencia de Recursos Materiais',
                'Gerencia de Apoio e Servicos Gerais',
                'Gerencia Operacional de Analise de DNA',
                'Carceragem 1 Superintendencia',
                'Carceragem 2 Superintendencia',
                'Carceragem 3 Superintendencia',
                'Carceragem 4 Superintendencia',
                'Chefia de Gabinete e Protocolo',
                'Assessoria Tecnico-Normativa',
                'Assessoria de Comunicacao',
                'Unidade de Planejamento, Licitacoes, Contratos, Projetos e Convenios',
                'Unidade de Orcamento e Financas',
                'Unidade de Tecnologia da Informacao',
                'Unidade de Estatistica Criminal e Analise de Dados',
                'Unidade de Engenharia e Recursos Imobiliarios',
                'Unidade de Controle Interno',
                'Nucleo de Laboratorio Forense - Cajazeiras',
                '3 Superintendencia do IPC',
                '4 Superintendencia do IPC',
                'Coordenacao Administrativa da 2 SRPC',
                'Nucleo de Identificacao Civil e Criminal - Campina Grande',
                'Nucleo de Identificacao Civil e Criminal - Guarabira',
                'Nucleo de Identificacao Civil e Criminal - Cajazeiras',
                'Nucleo de Identificacao Civil e Criminal - Patos',
                'Unidade de Apoio Operacional',
                '2 Delegacia Especializada de Atendimento a Mulher de Campina Grande',
                'Centro Integrado de Comando e Controle - Joao Pessoa',
                'Centro Integrado de Comando e Controle - Campina Grande',
                'Centro Integrado de Comando e Controle - Patos',
                'Delegacia Especializada de Atendimento a Mulher de Esperanca',
                'Delegacia Especializada de Atendimento a Mulher de Alhandra',
                'Delegacia Especializada de Atendimento a Mulher de Itaporanga',
                'Sem Lotacao'
            ];

            foreach ($unidades as $nome) {
                // Primeiro, criar ou buscar o team
                $team = Team::firstOrCreate(
                    ['name' => $nome], // Condição de busca
                    [
                        'user_id' => 1, // ID do usuário admin que deve ser criado primeiro
                        'personal_team' => false,
                    ]
                );

                // Depois, criar a unidade associada ao team (se não existir)
                Unidade::firstOrCreate(
                    ['team_id' => $team->id], // Condição de busca - uma unidade por team
                    [
                        'nome' => $nome,
                        'team_id' => $team->id,
                        'is_draft' => true, // Começar como rascunho
                        //'status' => 'pendente', // Status inicial
                    ]
                );
            }
        });
    }
}