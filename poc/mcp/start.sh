#!/usr/bin/env bash
# Prova de conceito do servidor MCP do WCJ Hub (D-GER-17, branch poc/mcp).
# Sobe o túnel HTTPS (cloudflared, túnel rápido) e o Laravel na porta 8010 com APP_URL = URL pública.
# Uso:  ./poc/mcp/start.sh                      registro dinâmico (RFC 7591)
#       ./poc/mcp/start.sh --cimd               também anuncia o Client ID Metadata Document
#       ./poc/mcp/start.sh --esconder-escrita   "anotar" some do tools/list da conexão só leitura
# Parar: ./poc/mcp/stop.sh
set -euo pipefail

RAIZ="$(cd "$(dirname "$0")/../.." && pwd)"
RUN="$RAIZ/storage/logs/poc-mcp"
PORTA=8010
CIMD=false
ESCONDER=false
for arg in "$@"; do
  case "$arg" in
    --cimd) CIMD=true ;;
    --esconder-escrita) ESCONDER=true ;;
    *) echo "Opção desconhecida: $arg"; exit 1 ;;
  esac
done

cd "$RAIZ"
mkdir -p "$RUN"

command -v cloudflared >/dev/null || { echo "Falta o cloudflared: brew install cloudflared"; exit 1; }
[[ -f .env ]] || { echo "Falta o .env (cp .env.example .env && php artisan key:generate)"; exit 1; }
[[ -f storage/oauth-private.key ]] || php artisan passport:keys -n >/dev/null

for nome in tunel servidor; do
  if [[ -f "$RUN/$nome.pid" ]] && kill -0 "$(cat "$RUN/$nome.pid")" 2>/dev/null; then
    echo "Já está rodando ($nome, PID $(cat "$RUN/$nome.pid")). Pare antes: ./poc/mcp/stop.sh"; exit 1
  fi
done
if lsof -nP -iTCP:$PORTA -sTCP:LISTEN >/dev/null 2>&1; then
  echo "A porta $PORTA já está em uso:"; lsof -nP -iTCP:$PORTA -sTCP:LISTEN; exit 1
fi

# Troca (ou acrescenta) uma variável do .env sem mexer no resto.
definir() {
  if grep -q "^$1=" .env; then
    sed -i '' "s|^$1=.*|$1=$2|" .env
  else
    printf '%s=%s\n' "$1" "$2" >> .env
  fi
}

echo "1/4 Subindo o túnel (cloudflared)..."
: > "$RUN/tunel.log"
cloudflared tunnel --no-autoupdate --url "http://127.0.0.1:$PORTA" > "$RUN/tunel.log" 2>&1 &
echo $! > "$RUN/tunel.pid"

URL=""
for _ in $(seq 1 60); do
  URL="$(grep -oE 'https://[a-z0-9-]+\.trycloudflare\.com' "$RUN/tunel.log" | head -1 || true)"
  [[ -n "$URL" ]] && break
  sleep 1
done
[[ -n "$URL" ]] || { echo "O túnel não devolveu URL em 60 s. Veja $RUN/tunel.log"; "$RAIZ/poc/mcp/stop.sh"; exit 1; }

echo "2/4 .env com a URL pública ($URL)..."
definir APP_URL "$URL"
definir APP_DEBUG false
definir SESSION_SECURE_COOKIE true
definir TRUSTED_PROXIES 127.0.0.1
definir POC_MCP_CIMD "$CIMD"
definir POC_MCP_ESCONDER_ESCRITA "$ESCONDER"
php artisan config:clear >/dev/null
php artisan route:clear >/dev/null
[[ -f public/build/manifest.json ]] || npm run build >/dev/null

echo "3/4 Subindo o Laravel na porta $PORTA..."
PHP_CLI_SERVER_WORKERS=4 php artisan serve --host=127.0.0.1 --port=$PORTA --no-reload > "$RUN/servidor.log" 2>&1 &
echo $! > "$RUN/servidor.pid"
for _ in $(seq 1 30); do
  curl -fs "http://127.0.0.1:$PORTA/up" >/dev/null 2>&1 && break
  sleep 1
done

echo "4/4 Conferindo os metadados pela URL pública (o DNS do túnel leva alguns segundos)..."
# Resolve pelo 1.1.1.1 e força o IP no curl: perguntar ao DNS do Mac antes de o nome existir deixa
# a resposta negativa em cache por alguns minutos (no seu navegador também, se abrir cedo demais).
HOST="${URL#https://}"
OK=false
for _ in $(seq 1 30); do
  IP="$(dig +short "$HOST" @1.1.1.1 2>/dev/null | grep -E '^[0-9.]+$' | head -1 || true)"
  if [[ -n "$IP" ]] && curl -fs --resolve "$HOST:443:$IP" "$URL/.well-known/oauth-protected-resource/mcp" >/dev/null 2>&1; then OK=true; break; fi
  sleep 2
done
$OK || echo "AVISO: a URL pública ainda não respondeu. Tente de novo em 1 minuto: curl $URL/.well-known/oauth-protected-resource/mcp"

cat <<FIM

Servidor MCP:   $URL/mcp
Metadados:      $URL/.well-known/oauth-protected-resource/mcp
                $URL/.well-known/oauth-authorization-server
Registro:       $URL/oauth/register   (dinâmico, RFC 7591)
CIMD:           $([[ $CIMD == true ]] && echo "anunciado" || echo "desligado (./poc/mcp/start.sh --cimd para ligar)")
Escrita oculta: $ESCONDER (só leitura não vê "anotar" quando true)
Login do Hub:   $URL/admin/login

Log da prova:   tail -f storage/logs/mcp.log
Parar:          ./poc/mcp/stop.sh
A URL muda a cada start: os conectores criados nos assistentes precisam ser refeitos.
FIM
