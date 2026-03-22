
# Container

```bash

docker exec -it hyperf-skeleton sh

```

# Rotas

```bash
curl -X POST "http://localhost:9501/account/123456/balance/withdraw" \
  -H "Content-Type: application/json" \
  -d '{
    "method": "PIX",
    "pix": {
      "type": "email",
      "key": "fulano@email.com"
    },
    "amount": 150.75,
    "schedule": "2026-01-01 15:00"
  }'
```

