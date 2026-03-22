
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

