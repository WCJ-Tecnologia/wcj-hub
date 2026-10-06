#!/usr/bin/env bash
# Para o túnel e o Laravel da prova do MCP pelos PIDs gravados no start.sh (nunca pkill -f)
# e devolve o .env ao modo local (http).
set -uo pipefail

RAIZ="$(cd "$(dirname "$0")/../.." && pwd)"
RUN="$RAIZ/storage/logs/poc-mcp"
PORTA=8010
cd "$RAIZ"

parar() {
  local arquivo="$RUN/$1.pid"
  [[ -f "$arquivo" ]] || return 0
  local pid; pid="$(cat "$arquivo")"
  if kill -0 "$pid" 2>/dev/null; then
    kill "$pid" && echo "Parado: $1 (PID $pid)"
  fi
  rm -f "$arquivo"
}

parar servidor
parar tunel

# O artisan serve encerra o php -S filho ao receber o sinal; se algum ficou na porta, para pelo PID.
sleep 1
for pid in $(lsof -tiTCP:$PORTA -sTCP:LISTEN 2>/dev/null); do
  if ps -p "$pid" -o command= | grep -q "php"; then
    kill "$pid" && echo "Parado: php na porta $PORTA (PID $pid)"
  fi
done

if [[ -f .env ]]; then
  sed -i '' -e "s|^APP_URL=.*|APP_URL=http://localhost:$PORTA|" \
            -e "s|^APP_DEBUG=.*|APP_DEBUG=true|" \
            -e "s|^SESSION_SECURE_COOKIE=.*|SESSION_SECURE_COOKIE=false|" .env
  php artisan config:clear >/dev/null 2>&1 || true
fi
echo "Pronto. .env de volta para http://localhost:$PORTA."
