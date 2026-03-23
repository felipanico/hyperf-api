
# Entrar no container da api

```bash

docker exec -it hyperf-skeleton sh

```

# Criar contas com saldo inicial

```bash

docker exec -it hyperf-skeleton php bin/hyperf.php db:seed

```

# Criar saque sem agendamento

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

# Criar saque com agendamento

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
    "schedule": "2026-03-22 21:10"
  }'
```

# Consultar data do próximo agendamento no Redis

```bash
docker exec -it redis redis-cli GET c:cron:withdraw:next-pending-scheduled-for

```

# Rodar Testes

```bash
docker exec -it hyperf-skeleton ./vendor/bin/pest test/Unit/WithdrawMethodTest.php

```

