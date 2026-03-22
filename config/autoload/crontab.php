<?php

use App\Infrastructure\Crontab\CheckWithdrawTask;
use Hyperf\Crontab\Crontab;

return [
    'enable' => true,
    'crontab' => [
        (new Crontab())
            ->setName('check-withdraw-task')
            ->setRule('* * * * *')
            ->setCallback([CheckWithdrawTask::class, 'execute'])
            ->setMemo('Verificação de saque a cada minuto'),
    ],
];
