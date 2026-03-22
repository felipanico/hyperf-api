<?php

declare(strict_types=1);

use App\Model\Account;
use Hyperf\Database\Seeders\Seeder;
use Hyperf\Stringable\Str;

class InitialDataSeeder extends Seeder
{
    private const PREFIX = 'Conta ';

    public function run(): void
    {
        foreach ($this->accounts() as $accountData) {
            Account::query()->updateOrCreate(
                ['id' => $accountData['id']],
                [
                    'name' => $accountData['name'],
                    'balance' => $accountData['balance'],
                ]
            );
        }
    }

    private function accounts(): array
    {
        return [
            [
                'id' => Str::uuid()->toString(),
                'name' => self::PREFIX. ' '. Str::random(5),
                'balance' => rand(100, 1000),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => self::PREFIX. ' '. Str::random(5),
                'balance' => rand(100, 1000),
            ],
        ];
    }
}
