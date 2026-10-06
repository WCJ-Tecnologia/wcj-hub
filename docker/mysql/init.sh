#!/bin/sh
# Runs once, on the first start of an empty volume (docker-entrypoint-initdb.d).
# Test and e2e databases next to the dev one, same user (ADR-002).
set -e
mysql -uroot -p"$MYSQL_ROOT_PASSWORD" <<SQL
CREATE DATABASE IF NOT EXISTS wcj_hub_test CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
CREATE DATABASE IF NOT EXISTS wcj_hub_e2e CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
GRANT ALL PRIVILEGES ON wcj_hub_test.* TO '$MYSQL_USER'@'%';
GRANT ALL PRIVILEGES ON wcj_hub_e2e.* TO '$MYSQL_USER'@'%';
FLUSH PRIVILEGES;
SQL
