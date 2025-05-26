<?php
// app/Models/Transaction.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Transaction extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'user_id',
        'amount',
        'type',
        'status',
        'previous_balance',
        'current_balance',
        'failure_reason',
        'idempotency_key',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'previous_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    /**
     * Get the user that owns the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();

        // Automatically generate a unique transaction ID
        static::creating(function ($transaction) {
            if (empty($transaction->transaction_id))
            {
                $transaction->transaction_id = self::generateUniqueTransactionId();
            }
        });
    }

    private static function generateUniqueTransactionId(): string
    {
        do
        {
            $id = 'TXN-'.strtoupper(Str::random(12));
        } while (self::where('transaction_id', $id)->exists());

        return $id;
    }
}
