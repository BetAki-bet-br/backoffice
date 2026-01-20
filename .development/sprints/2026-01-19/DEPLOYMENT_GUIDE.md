# 🚀 Guia de Deployment: Upload S3

**Versão**: 1.0.0  
**Data**: 19 de janeiro de 2026  
**Status**: ✅ PRONTO PARA PRODUÇÃO

---

## 📋 Checklist Pré-Deployment

### Desenvolvimento Local
- [x] Código implementado
- [x] Testes locais passando (12/12)
- [x] Validações funcionando
- [x] Componente Blade funcionando

### Antes de Deploy

- [ ] Verificar branch `main` está atualizado
- [ ] Fazer backup do banco de dados
- [ ] Verificar configurações de S3 em produção
- [ ] Testar credenciais de S3
- [ ] Verificar quotas de S3
- [ ] Limpar cache

---

## 🔧 Instruções de Deployment

### 1. Pull Latest Code

```bash
cd /seu/app/path
git pull origin main
```

### 2. Install Dependencies (se necessário)

```bash
composer install
```

### 3. Run Migrations

```bash
# Produção
php artisan migrate --force

# Ou se usar Docker
docker-compose exec app php artisan migrate --force
```

**O que a migration faz**:
- Adiciona coluna `cover_url` em `categories` table
- Adiciona coluna `cover_url` em `banners` table
- Slot já tem a coluna (foi criada na sprint anterior)

### 4. Cache Clear

```bash
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5. Restart Queue (se aplicável)

```bash
php artisan queue:restart
```

### 6. Test Upload Functionality

```bash
# Executar testes
php artisan test tests/Feature/FileUploadIntegrationTest.php

# Ou criar um slot com imagem via Postman/Curl
curl -X POST http://seu-dominio.com/api/v1/slots \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "title=Test Slot" \
  -F "provider=test" \
  -F "provider_game_id=test-123" \
  -F "cover_url=@/path/to/image.jpg" \
  -F "status=active"
```

---

## 🔒 Configuração de S3

### Variáveis de Ambiente

Adicionar ao `.env` (se não existir):

```env
AWS_ACCESS_KEY_ID=seu_access_key
AWS_SECRET_ACCESS_KEY=seu_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=seu_bucket_name
AWS_URL=https://seu_bucket_name.s3.amazonaws.com
AWS_ENDPOINT=https://s3.amazonaws.com
```

### Verificar Configuração

```bash
# Testar conexão com S3
php artisan tinker

# Dentro do tinker
Storage::disk('s3')->listContents('/');
```

### Permissões de Bucket

Certifique-se que a bucket S3 tem as permissões corretas:

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Principal": "*",
      "Action": "s3:GetObject",
      "Resource": "arn:aws:s3:::seu_bucket/*"
    },
    {
      "Effect": "Allow",
      "Principal": {
        "AWS": "arn:aws:iam::ACCOUNT_ID:user/seu_usuario"
      },
      "Action": [
        "s3:GetObject",
        "s3:PutObject",
        "s3:DeleteObject"
      ],
      "Resource": "arn:aws:s3:::seu_bucket/*"
    }
  ]
}
```

---

## 📊 Monitoramento Pós-Deployment

### 1. Verificar Logs

```bash
# Acompanhar logs em tempo real
tail -f storage/logs/laravel.log

# Ou se usar Docker
docker-compose logs -f app
```

### 2. Testar Endpoints

**Criar com imagem**:
```bash
curl -X POST https://seu-api.com/api/v1/slots \
  -H "Authorization: Bearer TOKEN" \
  -F "title=New Slot" \
  -F "provider=netent" \
  -F "provider_game_id=netent-game" \
  -F "cover_url=@image.jpg" \
  -F "status=active"
```

**Editar e trocar imagem**:
```bash
curl -X PUT https://seu-api.com/api/v1/slots/1 \
  -H "Authorization: Bearer TOKEN" \
  -F "title=Updated Slot" \
  -F "cover_url=@new-image.jpg"
```

### 3. Verificar S3

Via AWS Console:
1. Acessar S3 > seu_bucket
2. Navegar para pasta `/slots/2026/01/19/`
3. Verificar se arquivos estão lá
4. Clicar em arquivo > "Open" para confirmar acesso

### 4. Monitorar Erros

No AWS CloudWatch:
1. Acessar CloudWatch > Logs
2. Procurar por grupo de logs da aplicação
3. Filtrar por "cover_url" ou "FileUploadService"
4. Verificar erros de permissão ou conexão

---

## 🆘 Troubleshooting

### Erro: "SQLSTATE[42P07]: Duplicate table"

**Causa**: Migration já foi executada

**Solução**:
```bash
# Verificar status das migrations
php artisan migrate:status

# Se necessário, reverter apenas a última
php artisan migrate:rollback --step=1
```

### Erro: "could not translate host name"

**Causa**: Banco de dados não está acessível

**Solução**:
```bash
# Verificar conexão
php artisan tinker
DB::connection()->getPdo();

# Ou se usar Docker
docker-compose logs db
```

### Erro: "403 Forbidden" ao acessar S3

**Causa**: Credenciais de S3 incorretas ou permissões insuficientes

**Solução**:
```bash
# Verificar credenciais
php artisan env:set AWS_ACCESS_KEY_ID xxx
php artisan env:set AWS_SECRET_ACCESS_KEY xxx

# Testar acesso
php artisan tinker
Storage::disk('s3')->put('test.txt', 'test');
Storage::disk('s3')->delete('test.txt');
```

### Erro: "file_uploads not enabled"

**Causa**: PHP não permite upload de arquivos

**Solução**:
```ini
; php.ini
file_uploads = On
upload_max_filesize = 100M
post_max_size = 100M
```

### Imagens não aparecem (404)

**Causa**: URL do S3 incorreta ou arquivo foi deletado

**Solução**:
1. Verificar URL em `response.json('cover_url')`
2. Copiar URL no navegador
3. Se 404, verificar se arquivo existe em S3
4. Verificar permissões de leitura pública

---

## 📈 Performance

### Otimizações Implementadas

- ✅ Validação client-side (evita upload desnecessário)
- ✅ Preview em base64 (sem requisição extra)
- ✅ Nomes de arquivo aleatórios (segurança)
- ✅ Diretórios por data (organização)

### Melhorias Futuras

- Queue para uploads grandes
- Compressão automática
- CDN CloudFront para imagens
- Cache de URL do S3

---

## 🔄 Rollback

Se houver problemas, é possível reverter:

```bash
# Reverter última migration
php artisan migrate:rollback

# Reverter a migration específica
php artisan migrate:rollback --step=1

# Voltar código para versão anterior
git revert HEAD
```

---

## 📞 Suporte

### Logs Importantes

Verificar estes arquivos em caso de problema:

- `storage/logs/laravel.log` - Logs da aplicação
- AWS CloudWatch - Logs de S3
- `storage/database.sqlite` - Banco de dados local

### Debug

```bash
# Ativar modo debug
APP_DEBUG=true

# Ver migrations pendentes
php artisan migrate:status

# Ver jobs na queue
php artisan queue:failed

# Testar upload diretamente
php artisan tinker
$file = \Illuminate\Http\UploadedFile::fake()->image('test.jpg');
\App\Services\FileUploadService::uploadSlotImage($file);
```

---

## 📅 Agendamento

### Limpeza de Imagens Órfãs (Opcional)

Criar um comando artisan para deletar imagens que não têm registro no DB:

```bash
php artisan storage:cleanup-images
```

Executar via CRON:
```cron
0 2 * * * cd /seu/app && php artisan storage:cleanup-images >> /dev/null 2>&1
```

---

## ✅ Checklist Pós-Deployment

- [ ] Migrations executadas com sucesso
- [ ] Testes passando em produção
- [ ] Imagens sendo salvass em S3
- [ ] URLs de S3 funcionando
- [ ] Logs não mostram erros
- [ ] CloudWatch monitorando corretamente
- [ ] Backups configurados
- [ ] Documentação atualizada

---

## 📚 Documentação Relacionada

- [IMPLEMENTATION_COMPLETE.md](IMPLEMENTATION_COMPLETE.md) - Detalhes técnicos
- [COMPONENT_USAGE_GUIDE.md](COMPONENT_USAGE_GUIDE.md) - Como usar o componente
- [S3_FRONTEND_GAP_ANALYSIS.md](S3_FRONTEND_GAP_ANALYSIS.md) - Análise anterior

---

## 🎯 Resumo Rápido

**Tempo de deployment**: ~5-10 minutos

**Passos essenciais**:
1. `git pull origin main`
2. `composer install`
3. `php artisan migrate --force`
4. `php artisan cache:clear`
5. Testar upload

**Verificar**:
- Logs sem erros
- S3 recebendo arquivos
- URLs funcionando

---

**Status**: ✅ PRONTO PARA PRODUÇÃO  
**Última atualização**: 19 de janeiro de 2026
