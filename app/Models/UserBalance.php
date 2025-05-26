<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBalance extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user's balance.
     *
     * @return string
     */
    public static function getBalanceForUser(int $userId): string
    {
        $balance = self::where('user_id', $userId)->first();
        return $balance ? $balance->balance : '1000000.00';
    }

    /**
     * Create or update the user's balance.
     *
     * @param int $userId
     * @param string $amount
     * @return void
     */
    public static function createOrUpdateBalance(int $userId, string $amount): void
    {
        self::updateOrCreate(
            ['user_id' => $userId],
            ['balance' => $amount]
        );
    }

}
