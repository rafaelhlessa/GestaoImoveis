# Gestão de Imóveis

Sistema de gerenciamento de propriedades imobiliárias desenvolvido em Laravel 11, Vue 3 (Inertia.js), Tailwind CSS e MySQL.

## Tecnologias

* PHP 8.2+
* Laravel 11
* Vue.js 3 + Inertia.js
* Vite
* Tailwind CSS
* MySQL
* Leaflet & KML para mapas interativos

## Pré-requisitos

* PHP >= 8.2 com extensões: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO
* Composer
* Node.js >= 18
* NPM ou Yarn
* MySQL

## Instalação

1. Clone o repositório:

   ```bash
   git clone <URL_DO_REPOSITORIO> gestao-imoveis
   cd gestao-imoveis
   ```

2. Instale as dependências PHP:

   ```bash
   composer install
   ```

3. Instale as dependências JavaScript:

   ```bash
   npm install
   ```

4. Copie o arquivo de variáveis de ambiente e configure:

   ```bash
   cp .env.example .env
   ```

   Ajuste as variáveis em `.env`:

   ```dotenv
   APP_NAME=GestaoImoveis
   APP_URL=http://localhost

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=nome_do_banco
   DB_USERNAME=usuario
   DB_PASSWORD=senha

   MAIL_MAILER=smtp
   MAIL_HOST=smtp.exemplo.com
   MAIL_PORT=587
   MAIL_USERNAME=seu_usuario
   MAIL_PASSWORD=sua_senha
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=no-reply@seudominio.com
   MAIL_FROM_NAME="${APP_NAME}"
   ```

5. Gere a chave da aplicação:

   ```bash
   php artisan key:generate
   ```

## Migrations e Seeders

Execute as migrations e seeders:

```bash
php artisan migrate --seed
```

## Execução em Desenvolvimento

Compile e sirva os assets:

```bash
npm run dev
php artisan serve
```

Acesse em `http://localhost:8000`.

## Build para Produção

Compile ativos para produção:

```bash
npm run build
```

## Permissões de Diretórios

Defina permissão para `storage` e `bootstrap/cache`:

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## Scheduler

Adicione ao crontab para executar o scheduler:

```bash
* * * * * cd /caminho/para/gestao-imoveis && php artisan schedule:run >> /dev/null 2>&1
```

## Testes

Execute os testes automatizados:

```bash
php artisan test
```

## Implantação em Produção

1. Configure o servidor web (Nginx ou Apache) apontando o root para a pasta `public`.
2. Instale dependências e gere o build (conforme seção Instalação).
3. Ajuste `.env` para:

   ```dotenv
   APP_ENV=production
   APP_DEBUG=false
   ```
4. Configure HTTPS (certificado SSL).
5. Reinicie os serviços (PHP-FPM, Nginx/Apache).

## Autor

Rafael Lessa

## Licença

MIT
