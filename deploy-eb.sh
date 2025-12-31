#!/usr/bin/env bash
set -e

ZIP_NAME="deploy-eb.zip"

rm -f "$ZIP_NAME"
rm -rf .ebtmp
mkdir .ebtmp

# Copia tudo para staging (exclui lixos e coisas que não precisam ir no zip)
rsync -a \
  --exclude ".git" \
  --exclude ".ebtmp" \
  --exclude "node_modules" \
  --exclude "storage/logs" \
  --exclude ".env" \
  ./ .ebtmp/

# O EB quer docker-compose.yml na raiz do zip
# Então colocamos o compose do EB como docker-compose.yml dentro do pacote
rm -f .ebtmp/docker-compose.yml
cp docker-compose.eb.yml .ebtmp/docker-compose.yml

cd .ebtmp
zip -r "../$ZIP_NAME" .
cd ..

rm -rf .ebtmp
echo "✅ Gerado: $ZIP_NAME"