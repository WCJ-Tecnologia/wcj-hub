# Deploy: WCJ Hub

Runbook. **Esboço do B0**: o que já se sabe pelo escopo (B10) e pela D-GER-19; o resto se preenche no B10, com Alaor Rodrigues, antes do primeiro deploy. Regras em `.ai/core/deploy.md`. Nada aqui foi executado ainda.

## Ambientes

| Ambiente | URL | Servidor | Branch | Disparo |
|---|---|---|---|---|
| produção | `https://hub.wcjtecnologia.com.br` (DNS a criar) | containers do Hub na VPS do DaaS (D-GER-19) | `main` | manual no início; automático por merge na `main` depois que a homologação estabilizar (como o ADR-003 do sistema de obras) |
| homologação | a definir com o Alaor (precisa ser HTTPS público: os assistentes de IA chamam o `/mcp` da nuvem, B8) | mesma VPS | `main` | a definir no B10 |

## Variáveis de ambiente

Nome e **onde buscar o valor**. Nunca o valor. A lista completa, com os valores de dev, está no `.env.example`.

| Variável | Onde está o valor | Observação |
|---|---|---|
| `APP_KEY` | gerada no servidor (`php artisan key:generate`) | uma por ambiente; trocar invalida sessões |
| `APP_ENV`, `APP_DEBUG`, `APP_URL` | no servidor | `production`, `false`, a URL com `https://` |
| `APP_LOCALE` | no servidor | `pt_BR`; as mensagens de validação em português dependem dela |
| `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | cofre da WCJ, item "WCJ Hub — MySQL <ambiente>" (a criar no B10) | MySQL 8 próprio do Hub, separado do Supabase do DaaS (D-GER-14); usuário sem superusuário |
| `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS` | cofre da WCJ, item do SMTP Zoho (o mesmo do sistema de obras; guardado por Lucas Scholz, escopo §7) | `smtp`; sem isto o canal `mail` dos avisos (P22) não sai; nunca `log` em produção: a senha provisória do convite iria em claro para `storage/logs` |
| `PASSPORT_PRIVATE_KEY`, `PASSPORT_PUBLIC_KEY` | gerados no servidor (`php artisan passport:keys`), ou os arquivos `storage/oauth-*.key` | só a partir do B8 |
| `TRUSTED_PROXIES` | IP do Nginx/proxy da VPS | atrás de proxy |
| `SESSION_SECURE_COOKIE` | `true` | HTTPS |
| `HUB_AI_READS_PER_MINUTE`, `HUB_AI_WRITES_PER_MINUTE`, `HUB_AI_LOG_RETENTION_DAYS` | padrão do `config/hub.php` (60, 20, 180) | só mudar com decisão registrada |

## Ordem de deploy

A definir no B10 entre as duas (`.ai/core/banco.md` §6):

**A. O push dispara o deploy:** aplicar a migração em produção **antes** do push.

**B. A migração roda a partir do clone no servidor:** deploy do código → migração → verificar.

A tendência é a B, com um gate que recusa subir com migração pendente.

## Passo a passo (esboço)

```bash
# 1. backup do MySQL do Hub (confirmar a data do último e que a restauração foi testada)
# 2. atualizar o código no servidor (imagem ou git pull da main)
# 3. composer install --no-dev --optimize-autoloader && npm ci && npm run build
# 4. php artisan migrate --force
# 5. php artisan db:seed --force          (só DatabaseSeeder: domínios, listas, cliente WCJ; idempotente)
# 6. php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache
# 7. reiniciar php-fpm e o worker da fila (php artisan queue:work: os e-mails de aviso saem por ela)
```

Primeiro deploy, uma vez: `php artisan app:create-admin <e-mail>` (imprime a senha uma vez; a pessoa troca no primeiro acesso). Nunca `ExemploSeeder` em produção (ele mesmo se recusa).

## Verificação pós-deploy

- [ ] `curl -I https://<url>/up` responde 200
- [ ] `.env` e `.git/HEAD` respondem 403/404
- [ ] headers de segurança presentes, inclusive num asset
- [ ] login e o fluxo que mudou usados de verdade
- [ ] fila processando (`php artisan queue:monitor`)
- [ ] `php artisan migrate:status` sem pendência

## Voltar atrás

A definir no B10. Código: voltar a imagem ou o commit anterior. Banco: as migrações do Hub são aditivas; migração destrutiva só com plano de volta escrito antes (`.ai/core/banco.md` §5).

## Onde moram

| O quê | Caminho | No backup? |
|---|---|---|
| anexos das tarefas (P24, P36) | `storage/app/private` num volume fora da árvore do deploy | sim |
| chaves do Passport | `storage/oauth-*.key` ou variáveis | sim (cofre) |
| logs | `storage/logs`; o das chamadas da IA em canal diário, 180 dias (R13) | não |
| backup | destino externo a definir com o Alaor | último teste de restauração: nenhum ainda |

## Contatos

| Papel | Quem |
|---|---|
| infra (VPS, banco, DNS, backup) | Alaor Rodrigues |
| dono do sistema | Lucas Scholz |
