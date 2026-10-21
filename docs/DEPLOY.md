# Deploy: WCJ Hub

Runbook de publicação. Escrito no B9 (21/10/2026), a partir do esboço do B0. O que depende do servidor (imagem, nomes de container, URL da homologação, destino do backup) é **a definir no B10 com Alaor Rodrigues** e está marcado assim. Regras da casa em `.ai/core/deploy.md` e `.ai/core/seguranca.md`. Nada aqui foi executado em servidor ainda.

**Este arquivo não tem valor de segredo nenhum.** Cada variável aparece pelo nome, com o lugar de onde o valor vem. Segredo de produção nasce no servidor ou no cofre da WCJ e não passa por chat, ticket, commit nem documento (`seguranca.md` §9).

## Ambientes

| Ambiente | URL | Servidor | Branch | Disparo |
|---|---|---|---|---|
| produção | `https://hub.wcjtecnologia.com.br` (DNS a criar no B10) | containers do Hub na VPS do DaaS (D-GER-19) | `main` | manual no início; automático por merge na `main` depois que a homologação estabilizar (como o ADR-003 do sistema de obras) |
| homologação | a definir no B10. Precisa ser HTTPS público: os assistentes de IA chamam o `/mcp` da nuvem (B8) | mesma VPS | `main` | manual |

Os dois ambientes têm banco, `APP_KEY`, chaves do Passport e pasta de anexos próprios. Nada é compartilhado entre homologação e produção.

## Antes do primeiro deploy (uma vez por ambiente)

1. Domínio com HTTPS válido **antes** do primeiro usuário: a tela de login nunca vai ao ar em HTTP (`seguranca.md` §5).
2. MySQL 8 próprio do Hub, separado do Supabase do DaaS (D-GER-14), com um usuário só do Hub, sem privilégio de administrador do servidor.
3. Volume persistente para `storage/app/private` (anexos) e `storage/oauth-*.key`, fora da árvore do deploy.
4. Variáveis de ambiente preenchidas no servidor (tabela abaixo).
5. `php artisan key:generate` (gera a `APP_KEY` no servidor; uma por ambiente).
6. `php artisan passport:keys` (gera `storage/oauth-private.key` e `oauth-public.key`). As rotas `/oauth/*` só ligam no B8.
7. Nginx com o bloco da seção "Servidor web" e PHP com `expose_php = Off`.
8. Worker da fila sob supervisão (seção "Fila").
9. Backup diário do MySQL e da pasta de anexos ligado, **com uma restauração testada** antes de abrir para as pessoas.
10. Depois do primeiro deploy: `php artisan app:create-admin <e-mail>`. A senha aparece uma vez, no terminal de quem roda, e a pessoa troca no primeiro acesso. Nunca `ExemploSeeder` em produção (ele mesmo se recusa).

## Variáveis de ambiente

Todas as que o Hub lê estão no `.env.example`, com valores de dev. Em produção:

| Variável | Valor em produção | Onde está o valor |
|---|---|---|
| `APP_NAME` | `WCJ Hub` | fixo |
| `APP_ENV` | `production` | fixo |
| `APP_DEBUG` | `false` | fixo. `app:conferir-producao` recusa `true` |
| `APP_KEY` | gerada | no servidor, por `php artisan key:generate`. Trocar derruba todas as sessões |
| `APP_URL` | a URL do ambiente, com `https://` | DNS do B10 |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE` | `pt_BR`, `en` | fixo. As mensagens de validação em português dependem dela |
| `APP_FAKER_LOCALE` | `pt_BR` | fixo; sem uso em produção |
| `APP_DISPLAY_TIMEZONE` | `America/Sao_Paulo` | fixo. O banco guarda UTC |
| `APP_MAINTENANCE_DRIVER` | `file` | fixo |
| `APP_BRAND_NAME`, `APP_THEME_COLOR` | `WCJ Hub`, a cor do tema | fixo (`docs/brand/`) |
| `TRUSTED_PROXIES` | vazio, com Nginx falando FastCGI com o PHP; o IP de um proxy HTTP só se houver um entre o Nginx e o PHP. **Nunca `*`** | rede da VPS (Alaor). Ver "IP real" na seção do Nginx |
| `BCRYPT_ROUNDS` | `12` | fixo |
| `LOG_CHANNEL`, `LOG_STACK`, `LOG_LEVEL` | `stack`, `daily`, `warning` | fixo. `debug` em produção enche o disco e pode gravar dado demais |
| `LOG_DEPRECATIONS_CHANNEL` | `null` | fixo |
| `DB_CONNECTION`, `DB_PORT`, `DB_COLLATION` | `mysql`, `3306`, `utf8mb4_0900_ai_ci` | fixo |
| `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | do MySQL do ambiente | cofre da WCJ, item "WCJ Hub — MySQL <ambiente>" (a criar no B10) |
| `SESSION_DRIVER` | `database` | fixo. Trocar a senha encerra as outras sessões apagando as linhas da tabela `sessions`; com outro driver isso para de funcionar |
| `SESSION_LIFETIME` | `120` | fixo (minutos) |
| `SESSION_ENCRYPT` | `false` | fixo. A sessão não guarda senha nem token; ligar é opção, não exigência |
| `SESSION_PATH`, `SESSION_DOMAIN` | `/`, vazio | fixo |
| `SESSION_SECURE_COOKIE` | `true` | fixo. `app:conferir-producao` recusa `false` |
| `BROADCAST_CONNECTION` | `log` | fixo; sem uso |
| `FILESYSTEM_DISK` | `local` | fixo. Anexos em `storage/app/private`, nunca em `public/` |
| `QUEUE_CONNECTION` | `database` | fixo. `app:conferir-producao` recusa `sync` |
| `CACHE_STORE` | `database` | fixo. O limitador de tentativas mora no cache |
| `REDIS_HOST`, `REDIS_PASSWORD`, `REDIS_PORT` | não usados com `CACHE_STORE=database` | só se o B10 trocar o cache para Redis; senha no cofre |
| `AUTH_THROTTLE_PER_ACCOUNT`, `AUTH_THROTTLE_PER_IP` | `5`, `20` | fixo (`seguranca.md` §3). Só mudar com decisão registrada |
| `AUTH_TIMEBOX_MS` | `500` | fixo. Tempo mínimo de todo login, "esqueci a senha" e "redefinir" recusados, para o tempo não revelar se o e-mail tem conta (B9). Tem de ficar acima do custo do bcrypt no servidor: confira com `php artisan tinker --execute="\$t=microtime(true); Hash::make('x'); echo round((microtime(true)-\$t)*1000);"` e, se passar de ~400 ms, suba o valor |
| `CORS_ALLOWED_ORIGINS` | vazio | fixo. Só `api/health` aceita CORS, e nenhuma origem precisa dele hoje |
| `MAIL_MAILER` | `smtp` | fixo. `app:conferir-producao` recusa `log` e `array`: com `log`, a senha provisória do convite iria em claro para `storage/logs` |
| `MAIL_SCHEME`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_ENCRYPTION` | os do SMTP Zoho da WCJ | o host, a porta e a criptografia estão no comentário do `.env.example` |
| `MAIL_USERNAME`, `MAIL_PASSWORD` | a conta de envio | cofre da WCJ, item do SMTP Zoho (o mesmo do sistema de obras; guardado por Lucas Scholz, escopo §7) |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | o remetente da WCJ | definido com Lucas Scholz no B10 |
| `PASSPORT_PRIVATE_KEY`, `PASSPORT_PUBLIC_KEY` | vazias, se os arquivos `storage/oauth-*.key` existirem no volume | gerados no servidor por `php artisan passport:keys`; cópia no cofre, item "WCJ Hub — Passport <ambiente>". Só a partir do B8 |
| `HUB_AI_READS_PER_MINUTE`, `HUB_AI_WRITES_PER_MINUTE`, `HUB_AI_LOG_RETENTION_DAYS` | `60`, `20`, `180` | padrão do `config/hub.php`. Só mudar com decisão registrada |
| `HUB_ATTACHMENT_MAX_KB`, `HUB_ATTACHMENT_MAX_FILES` | `20480`, `10` | padrão do `config/hub.php`. O PHP e o Nginx precisam aceitar esse tamanho (seção "Servidor web") |
| `VITE_APP_NAME` | `${APP_NAME}` | fixo; lida no build do front |

## Conferência (passo zero de todo deploy)

```bash
php artisan app:conferir-producao
```

Sai com 1 e lista, pelo nome da variável, o que não pode ir para produção: `APP_ENV` diferente de `production`, `APP_DEBUG` ligado, `APP_URL` sem `https://`, `APP_KEY` vazia, `SESSION_SECURE_COOKIE` desligado, `MAIL_MAILER` em `log` ou `array`, `QUEUE_CONNECTION` em `sync`. Nunca imprime valor. **Saiu com 1, o deploy não continua.** Rode depois de carregar as variáveis do servidor e, se usar `config:cache`, depois dele também (o comando lê a configuração carregada).

## Ordem de deploy

O Hub usa a ordem **B** do `.ai/core/banco.md` §6: deploy do código, migração a partir do código novo, verificação. As migrações do Hub são aditivas (coluna ou tabela nova, nunca apagar ou reescrever), então o código anterior continua rodando com o banco migrado e dá para voltar o código sem voltar o banco. Migração destrutiva só com plano de volta escrito antes (`.ai/core/banco.md` §5) e aprovação humana.

## Passo a passo

Os comandos rodam no container da aplicação (nome a definir no B10).

```bash
# 0. conferência
php artisan app:conferir-producao

# 1. backup do MySQL e da pasta de anexos; anotar a data e confirmar que o último teste de restauração passou
# 2. código novo: a imagem da main, ou git fetch && git checkout <commit da main>
# 3. dependências e build
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build
# 4. migração
php artisan migrate --force
# 5. sementes (só DatabaseSeeder: domínios, listas, cliente WCJ; idempotente)
php artisan db:seed --force
# 6. caches
php artisan optimize:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan app:conferir-producao        # de novo, sobre a configuração em cache
# 7. reiniciar
#    php-fpm (ou o container da aplicação)
php artisan queue:restart                # o worker termina o job atual e sobe com o código novo
```

## Fila

Saem pela fila o "esqueci a senha" (B9: a resposta não espera o SMTP; quem iguala o tempo entre conta que existe e que não existe é o Timebox de `AUTH_TIMEBOX_MS`) e os avisos de dev e QA (`TaskAssigned` e `TaskReturned`, P22). **Sem worker rodando, nenhum desses e-mails sai.** O convite com a senha provisória sai na própria requisição de quem convida (o Administrador vê o erro na hora se o SMTP falhar).

- Um processo `php artisan queue:work --tries=3 --max-time=3600` sob supervisão (supervisord, ou um container próprio com `restart: unless-stopped`), definido no B10.
- `php artisan queue:restart` a cada deploy (passo 7).
- Job que falhou fica em `failed_jobs`: `php artisan queue:failed` para ver, `php artisan queue:retry <id>` para reenviar.
- O payload do job do "esqueci a senha" leva o link com o token até o envio. O token expira em 60 minutos (`config/auth.php`); quem lê o banco já lê tudo do Hub.

## Servidor web (Nginx)

Os headers de segurança saem da própria aplicação, em toda resposta do PHP (`app/Http/Middleware/SecurityHeaders.php`, global desde o B9). O Nginx cuida do que não passa pelo PHP: os arquivos de `public/` (build do front, fontes, logo). Bloco de referência, ajustado no B10 com o Alaor:

```nginx
# Qualquer Host que não seja o do Hub fecha a conexão sem resposta (B9, SEC-21). A aplicação
# também recusa (trustHosts, 400) e gera todo link a partir do APP_URL; o Nginx é a primeira porta.
server {
    listen 80 default_server;
    server_name _;
    return 301 https://hub.wcjtecnologia.com.br$request_uri;   # o host do APP_URL do ambiente
}

server {
    listen 443 ssl default_server;
    server_name _;
    # ssl_certificate e ssl_certificate_key: os mesmos do Hub (ou ssl_reject_handshake on;)
    return 444;
}

server {
    listen 443 ssl;
    server_name hub.wcjtecnologia.com.br;   # o host do APP_URL do ambiente

    server_tokens off;
    client_max_body_size 210m;      # HUB_ATTACHMENT_MAX_KB x HUB_ATTACHMENT_MAX_FILES, com folga

    # IP real, se houver proxy ou balanceador na frente do Nginx (seguranca.md §8)
    # set_real_ip_from <IP ou faixa do proxy>;
    # real_ip_header X-Forwarded-For;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Todo caminho começado por ponto (.env, .git), menos o .well-known (certificado; metadados OAuth do B8)
    location ~ /\.(?!well-known) {
        deny all;
        return 404;
    }

    # Backup, dump e diagnóstico nunca são servidos
    location ~* \.(bak|old|orig|swp|sql|sqlite|log|tar|gz|zip|dist)$ {
        deny all;
        return 404;
    }

    # Assets: add_header dentro de um location descarta os herdados; repita o bloco inteiro
    location ~* ^/(build|fonts|brand)/ {
        add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
        add_header X-Content-Type-Options "nosniff" always;
        add_header X-Frame-Options "SAMEORIGIN" always;
        add_header Referrer-Policy "strict-origin-when-cross-origin" always;
        expires 1y;
        access_log off;
        try_files $uri =404;
    }

    location ~ \.php$ {
        fastcgi_hide_header X-Powered-By;
        # fastcgi_pass, include fastcgi_params, SCRIPT_FILENAME: do container do B10
    }
}
```

No PHP (`php.ini` do container): `expose_php = Off`, `upload_max_filesize = 20M`, `post_max_size = 210M`, `max_file_uploads = 10` (os mesmos números do `config/hub.php`).

**IP real.** O limite de tentativas por IP só funciona se o PHP enxergar o IP de quem acessa (`seguranca.md` §8).

- Nginx falando com o PHP-FPM por FastCGI (o caso esperado): o PHP recebe o IP do cliente em `REMOTE_ADDR`, passado pelo próprio Nginx. `TRUSTED_PROXIES` fica **vazio**, mesmo com os dois em containers separados.
- Proxy ou balanceador na frente do Nginx: o IP real entra pelo `set_real_ip_from` e `real_ip_header` do Nginx (bloco acima), não pelo `TRUSTED_PROXIES`.
- `TRUSTED_PROXIES` só leva o IP de um proxy HTTP que fica entre o Nginx e o PHP (PHP servido por HTTP, não FastCGI).
- **Nunca `*`.** Com `*`, qualquer um forja `X-Forwarded-For` e escapa do teto de 20 tentativas por IP.

## Verificação pós-deploy

Cada item com o comando e a resposta esperada. Qualquer um diferente: voltar atrás (seção abaixo).

- [ ] `php artisan app:conferir-producao` sai com 0
- [ ] `php artisan migrate:status` sem migração pendente
- [ ] `curl -sI https://<url>/up` responde 200 com `Content-Security-Policy`, `Strict-Transport-Security` e `X-Content-Type-Options: nosniff`
- [ ] `curl -sI https://<url>/caminho-que-nao-existe` responde 404 com os mesmos headers
- [ ] `curl -sI https://<url>/build/manifest.json` (ou outro asset) traz `X-Content-Type-Options` e `Strict-Transport-Security`
- [ ] nenhuma das respostas acima traz `X-Powered-By` nem a versão do Nginx em `Server`
- [ ] `curl -s -o /dev/null -w '%{http_code}' https://<url>/.env` responde 403 ou 404, nunca 200; o mesmo para `/.git/HEAD`, `/storage/qualquer-coisa` e `/index.php.bak`
- [ ] `curl -s -o /dev/null -w '%{http_code}' http://<url>/` redireciona para `https://`
- [ ] `curl -sk -o /dev/null -w '%{http_code}' -H 'Host: outro.exemplo' https://<IP do servidor>/admin/login` não responde 200 (o Nginx fecha com 444; se chegar à aplicação, ela responde 400)
- [ ] login com uma conta de verdade e o fluxo que mudou usados no navegador
- [ ] fila processando: `php artisan queue:monitor database:default` e um "esqueci a senha" de teste chegando na caixa (é o e-mail que passa pela fila)
- [ ] log do Nginx mostra o IP real de quem acessa, não o do proxy

## Voltar atrás

- **Código:** voltar para a imagem ou o commit anterior da `main`, refazer os passos 3, 6 e 7 e a verificação. Como as migrações do Hub são aditivas, o código anterior roda com o banco já migrado: não se volta migração para voltar código.
- **Banco:** só para dado perdido ou migração destrutiva que deu errado. Restaurar o backup do passo 1 num banco novo, conferir, e só então apontar `DB_DATABASE` para ele. Nunca `migrate:rollback` em produção sem plano escrito e aprovado.
- **Segredo vazado** (`APP_KEY`, senha do banco, SMTP, chaves do Passport): 1) trocar a credencial, 2) tirar de onde vazou, 3) limpar histórico. Só o passo 1 protege (`seguranca.md` §9). Trocar a `APP_KEY` derruba todas as sessões; trocar as chaves do Passport derruba todas as conexões de IA.

## Backup

| O quê | Como | Frequência | Retenção | Destino |
|---|---|---|---|---|
| MySQL do Hub | `mysqldump --single-transaction` (ou o snapshot que o Alaor usa na VPS) | diário e antes de todo deploy | a definir no B10 | externo à VPS, a definir no B10 |
| Anexos (`storage/app/private`) | cópia do volume | diário | igual ao banco | igual ao banco |
| Chaves do Passport (`storage/oauth-*.key`) | cópia no cofre da WCJ | quando forem geradas | permanente | cofre |

Backup sem restauração testada não conta. Último teste de restauração: **nenhum ainda** (fazer no B10, antes de abrir para as pessoas).

## Onde moram

| O quê | Caminho | No backup? |
|---|---|---|
| anexos das tarefas (P24, P36) | `storage/app/private`, num volume fora da árvore do deploy. Não há rota pública para eles: saem só pelo download do card, com a Policy (B9) | sim |
| chaves do Passport | `storage/oauth-*.key` ou as duas variáveis | sim (cofre) |
| logs | `storage/logs`, diário; o das chamadas da IA em canal próprio, 180 dias (R13, B8) | não |
| sessões, cache, fila | tabelas `sessions`, `cache`, `jobs`, `failed_jobs` do MySQL | junto com o banco |

## Contatos

| Papel | Quem |
|---|---|
| infra (VPS, banco, DNS, backup, Nginx) | Alaor Rodrigues |
| dono do sistema, segredos do SMTP | Lucas Scholz |
