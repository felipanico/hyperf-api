<?php

declare(strict_types=1);

use App\Enum\WithdrawMethod;
use ValueError;

test('withdraw method enum exposes pix as supported method', function () {
    expect(WithdrawMethod::values())->toBe(['PIX']);
});

test('throws an error for an unsupported withdraw method', function () {
    expect(fn () => WithdrawMethod::from('TED'))
        ->toThrow(ValueError::class);
});
