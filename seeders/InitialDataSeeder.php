<?php

declare(strict_types=1);

use App\Model\Account;
use Hyperf\Database\Seeders\Seeder;
use Hyperf\Stringable\Str;

class InitialDataSeeder extends Seeder
{
    private const PREFIX = 'Conta ';
    private const UUID = '11111111-2222-3333-4444-555555555555';

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

    /**
     * Primeira empresa criada de forma fixa
     * para facilitar testes e documentação
     *
     * @return array
     */
    private function accounts(): array
    {
        return [
            [
                'id' => self::UUID,
                'name' => self::PREFIX,
                'balance' => rand(100, 1000),
            ],
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
