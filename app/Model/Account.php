<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\Database\Model\Relations\HasMany;

class Account extends Model
{
    protected ?string $table = 'account';

    protected string $primaryKey = 'id';

    public bool $incrementing = false;

    protected string $keyType = 'string';

    public bool $timestamps = false;

    protected array $fillable = [
        'id',
        'name',
        'balance',
    ];

    protected array $casts = [
        'balance' => 'decimal:2',
    ];

    public function withdraws(): HasMany
    {
        return $this->hasMany(AccountWithdraw::class, 'account_id', 'id');
    }
}
