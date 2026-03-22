
# Container

```bash

docker exec -it hyperf-skeleton sh

```

# Seeder

```bash

docker exec -it hyperf-skeleton php bin/hyperf.php db:seed

```

# Rotas

```bash
curl -X POST "http://localhost:9501/account/4d440fb6-3582-45d7-b334-aa20f386a7db/balance/withdraw" \
  -H "Content-Type: application/json" \
  -d '{
    "method": "PIX",
    "pix": {
      "type": "email",
      "key": "fulano@email.com"
    },
    "amount": 150.75,
    "schedule": null
  }'
```

# Saldo

```mysql

UPDATE account_withdraw SET scheduled_for = '2026-03-22 20:40:00', done=0, scheduled=1 WHERE id= '0ba474c4-9567-4a86-bbf3-294d8917bad6';

```

