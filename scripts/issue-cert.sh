#!/bin/sh
set -e

if [ -z "$1" ]; then
  echo "Usage: $0 domain [--staging]"
  exit 1
fi

DOMAIN=$1
STAGING_FLAG=""
if [ "$2" = "--staging" ]; then
  STAGING_FLAG="--staging"
fi

# Verificação rápida: aborta se domínio for local (localhost, 127.*, ::1, *.local)
case "$DOMAIN" in
  localhost|127.*|::1|*.local)
    echo "Error: Let's Encrypt cannot issue certificates for local domains (e.g. localhost, 127.*, ::1, *.local)."
    echo "Use a real public domain or generate a self-signed certificate manually for local testing."
    exit 1
    ;;
  *)
    ;;
esac

# Gera config a partir do template
sed "s/{{DOMAIN}}/$DOMAIN/g" docker/nginx/default.conf.template > docker/nginx/default.conf

# Certifique-se de que webroot e letsencrypt existam
mkdir -p docker/nginx/certbot-www
mkdir -p letsencrypt/live/$DOMAIN

# Subir serviço app (PHP) antes do nginx para resolver upstream 'app'
echo "Bringing up app service..."
docker compose up -d --no-deps --build app

# Subir nginx (vai depender de app)
echo "Bringing up nginx..."
docker compose up -d --no-deps --build nginx

# Se o domínio for localhost ou um host não roteável, gerar self-signed
case "$DOMAIN" in
  localhost|127.*|::1|*.local)
    echo "Domain is '$DOMAIN' — Let's Encrypt won't issue for this. Generating self-signed certificate..."
    docker run --rm -v "$PWD/letsencrypt:/etc/letsencrypt" debian:12-slim sh -c "apt-get update >/dev/null && apt-get install -y openssl >/dev/null && mkdir -p /etc/letsencrypt/live/$DOMAIN && openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /etc/letsencrypt/live/$DOMAIN/privkey.pem -out /etc/letsencrypt/live/$DOMAIN/fullchain.pem -subj \"/CN=$DOMAIN\""
    ;;
  *)
    echo "Requesting certificate from Let's Encrypt for: $DOMAIN"
    docker compose run --rm certbot certonly --webroot -w /var/www/certbot $STAGING_FLAG -d $DOMAIN --agree-tos --no-eff-email -m admin@$DOMAIN
    ;;
esac

# Depois de emitir ou gerar, recarrega nginx para usar certificado
echo "Reloading nginx to apply certificates..."
docker compose exec nginx nginx -s reload || docker compose restart nginx

echo "Process finished for: $DOMAIN"

exit 0
