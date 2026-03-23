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
docker exec -it hyperf-skeleton php bin/hyperf.php migrate
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

Para rodar os testes

```bash
docker exec -it hyperf-skeleton ./vendor/bin/pest test/Unit/WithdrawMethodTest.php

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

Interface web:

```text
http://localhost:3001
```

## Comandos úteis

Os exemplos de uso e testes da aplicação estão no arquivo:

`docs/commands.md`

## Considerações para produção

Em um ambiente de produção, este projeto pode ser evoluído para suportar maior volume de processamento e melhor capacidade de observação da aplicação.

### Escalabilidade

Para escalar a aplicação, é possível ajustar melhor as configurações do Hyperf de acordo com o ambiente de execução e com a carga esperada.

Alguns exemplos:
- aumentar a quantidade de `workers`
- ajustar `max_requests`
- revisar a configuração de processos e crontabs

Também é recomendável mover tarefas mais custosas para rotinas assíncronas, evitando bloquear o fluxo principal da API.

Exemplos:
- envio de e-mails
- processamento de saques agendados em filas
- integrações com serviços externos
- geração de notificações e eventos

Dependendo da necessidade, isso pode ser feito com filas, workers dedicados, mensageria e consumidores assíncronos.

### Observabilidade

Para produção, é recomendável utilizar ferramentas de observabilidade mais robustas do que as usadas localmente.

Alguns exemplos:
- Prometheus
- Grafana
- Loki
- Elasticsearch
- Datadog
- Sentry

Com essas ferramentas, é possível acompanhar melhor:
- métricas da aplicação
- consumo de CPU e memória
- latência das rotas
- falhas em processos assíncronos
- erros de negócio
- logs centralizados
- tracing distribuído

Essas melhorias ajudam a tornar a aplicação mais preparada para cenários reais de produção, com maior estabilidade, capacidade de diagnóstico e facilidade de crescimento.
