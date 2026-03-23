# API Pix

Repositório do projeto:

```bash
git clone https://github.com/felipanico/hyperf-api
cd hyperf-api
cp .env.example .env # preencher DB_PASSWORD e OBSERVABILITY_TOKEN
```

## Como subir com Docker Compose

Com Docker e Docker Compose instalados, execute:

```bash
docker compose up -d --build
```

A aplicação ficará disponível em:

```text
http://localhost:9501
```

Para criar os dados iniciais:

```bash
docker exec -it hyperf-skeleton php bin/hyperf.php db:seed

```

Para realizar uma operação com uma das contas criada no passo acima:

```bash
curl -X POST "http://localhost:9501/account/11111111-2222-3333-4444-555555555555/balance/withdraw" \
  -H "Content-Type: application/json" \
  -d '{
    "method": "PIX",
    "pix": {
      "type": "email",
      "key": "fulano@email.com"
    },
    "amount": 1.50,
    "schedule": null
  }'
```


Para acompanhar os logs da aplicação:

```bash
docker compose logs -f hyperf-skeleton
```

Para derrubar o ambiente:

```bash
docker compose down
```

## Serviços do docker-compose

### hyperf-skeleton

Container principal da aplicação Hyperf.

Responsabilidades:
- executar a API
- expor as rotas HTTP
- processar as regras de negócio
- executar o cron configurado no projeto

### mysql

Banco de dados MySQL 8 da aplicação.

Responsabilidades:
- armazenar contas
- armazenar saques
- armazenar dados de PIX
- persistir os dados da aplicação

### redis

Servidor de cache da aplicaçãoo.

Responsabilidades:
- armazenar dados em cache
- auxiliar no controle de saques agendados
- reduzir consultas desnecessárias ao banco

### mailhog

Serviço utilizado para testes locais de envio de e-mail.

Responsabilidades:
- capturar os e-mails enviados pela aplicação
- permitir inspeçãoo manual dos e-mails sem envio real
- Cadastre um monitoramento do tipo push e insira as credenciais conforme o .env.example

Interface web:

```text
http://localhost:8025
```

### uptime-kuma

Serviço de observabilidade do ambiente local.

Responsabilidades:
- receber heartbeat do cron
- ajudar no monitoramento das execuções agendadas

## Comandos úteis

Os exemplos de uso e testes da aplicação estão no arquivo:

`docs/commands.md`
