# Sistema de Censo de Imóveis - Polícia Civil

<div align="center">
  <img src="public/images/logo-pc.png" alt="Logo Polícia Civil" width="150">
  <h3>Sistema de cadastro e avaliação de imóveis da Polícia Civil</h3>
</div>

## 📋 Sobre o Projeto

Este sistema permite o cadastramento, gerenciamento e avaliação dos imóveis utilizados pela Polícia Civil. Desenvolvido com Laravel e Vue.js (Inertia), oferece uma interface moderna e responsiva para todos os tipos de usuários, desde servidores até administradores.

### Principais Funcionalidades

- 🏢 **Cadastro completo de unidades policiais**
  - Informações gerais (nome, código, localização)
  - Dados estruturais do imóvel
  - Acessibilidade
  - Fotos e documentação visual

- 👮 **Gestão de usuários com níveis de acesso**
  - Super Administradores (acesso total)
  - Administradores (gerenciam suas unidades)
  - Servidores (visualizam informações)

- ⭐ **Sistema de avaliação de imóveis**
  - Aprovação de cadastros
  - Avaliação com notas para estrutura e acessibilidade
  - Histórico de avaliações

- 📊 **Gestão de informações**
  - Contratos de locação
  - Termos de cessão
  - Órgãos que compartilham imóveis

## 🔧 Tecnologias Utilizadas

### Backend
- [PHP 8.2+](https://www.php.net/) - Foi desenvolvido com o PHP 8.4.1
- [Laravel 12](https://laravel.com/)
- [Laravel Jetstream](https://jetstream.laravel.com/) - Autenticação e gerenciamento de equipes
- [PostgreSQL 17](https://www.postgresql.org/) - Banco de dados

### Frontend
- [Vue.js 3.0](https://vuejs.org/)
- [Inertia.js 2.0](https://inertiajs.com/) - SPA sem API
- [Tailwind CSS](https://tailwindcss.com/) - Framework CSS
- [Heroicons](https://heroicons.com/) - Conjunto de ícones

### Serviços Externos
- [TomTom API](https://developer.tomtom.com/) - Geocodificação de endereços

## 🚀 Instalação e Configuração

### Pré-requisitos
- PHP 8.2 ou superior
- Composer
- Node.js e NPM
- PostgreSQL
- Servidor web

### Passos para Instalação

1. **Clone o repositório**

2. **Instale as dependências PHP**
   ```bash
   composer install
   ```

3. **Instale as dependências JavaScript**
   ```bash
   npm install
   ```

4. **Configure o ambiente**
   ```bash
   cp .env.example .env
   # Edite o arquivo .env com as configurações do seu ambiente
   ```

5. **Gere a chave da aplicação**
   ```bash
   php artisan key:generate
   ```

6. **Execute as migrações e seeders**
   ```bash
   php artisan migrate --seed
   ```

7. **Crie o link simbólico para o storage**
   ```bash
   php artisan storage:link
   ```

8. **Compile os assets**
   ```bash
   npm run dev   # Para desenvolvimento
   # ou
   npm run build # Para produção
   ```

9. **Inicie o servidor de desenvolvimento**
   ```bash
   php artisan serve
   ```

### Usando Docker (opcional)
O projeto inclui configuração Docker via Laravel Sail:

```bash
# Iniciar os containers
./vendor/bin/sail up -d

# Executar comandos dentro do container
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

## 🔍 Como Usar o Sistema

### Acessando o Sistema

Após a instalação, você pode acessar o sistema com as seguintes credenciais padrão:

**Super Administrador**
- Matrícula: 0000001
- Senha: DITI@pcpb1981

**Administradores**
- Inicialmente todos os usuários são Administradores e podem cadastrar sua Unidade

### Fluxo de Trabalho

1. **Cadastro de Unidades**
   - Administradores cadastram suas unidades
   - Preenchem informações gerais, estruturais e de acessibilidade
   - Fazem upload de fotos exigidas

2. **Avaliação**
   - Super Administradores revisam as informações
   - Aprovam ou rejeitam cadastros
   - Realizam avaliações técnicas

3. **Consulta e Atualização**
   - Servidores podem visualizar informações
   - Administradores podem atualizar dados quando necessário

## 📦 Preparando para Produção

Antes de colocar o sistema em produção, execute:

```bash
# Otimizar dependências
composer install --no-dev --optimize-autoloader

# Otimizar Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Compilar assets
npm run build
```

## 🔐 Segurança

O sistema utiliza as seguintes medidas de segurança:

- Autenticação por matrícula e senha
- Autorização baseada em papéis
- Proteção CSRF em formulários
- Validação de dados
- Armazenamento seguro de senhas com hash

## 👨‍💻 Autores

- Seu Nome - [GitHub](https://github.com/seu-usuario) - email@exemplo.com

## 🙏 Agradecimentos

- [Laravel](https://laravel.com/) - Framework PHP
- [Vue.js](https://vuejs.org/) - Framework JavaScript
- [Tailwind CSS](https://tailwindcss.com/) - Framework CSS
- [TomTom](https://developer.tomtom.com/) - API de Geocodificação
