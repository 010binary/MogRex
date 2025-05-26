<?php
// app/Services/TransactionService.php
namespace App\Services;

use App\Jobs\ProcessTransaction;
use App\Models\Transaction;
use App\Models\UserBalance;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function createTransaction(int $userId, float $amount, string $type, ?string $idempotencyKey = null): Transaction
    {
        // Check for duplicate transaction using idempotency key
        if ($idempotencyKey)
        {
            $existingTransaction = Transaction::where('idempotency_key', $idempotencyKey)
                ->where('user_id', $userId)
                ->first();

            if ($existingTransaction)
            {
                return $existingTransaction;
            }
        }

        return DB::transaction(function () use ($userId, $amount, $type, $idempotencyKey) {
            // Ensure user has a balance record with default value
            $userBalance = UserBalance::firstOrCreate(
                ['user_id' => $userId],
                ['balance' => '1000000.00']
            );

            // Get current balance for immediate response
            $currentBalance = $userBalance->balance;

            // Calculate what the new balance would be (for response only)
            $estimatedNewBalance = $type === 'credit'
                ? bcadd($currentBalance, $amount, 2)
                : bcsub($currentBalance, $amount, 2);

            // Create transaction record
            $transaction = Transaction::create([
                'user_id' => $userId,
                'amount' => number_format($amount, 2, '.', ''),
                'type' => $type,
                'status' => 'pending',
                'idempotency_key' => $idempotencyKey,
                'previous_balance' => $currentBalance,
                'current_balance' => $estimatedNewBalance,
            ]);

            // Dispatch job for background processing
            ProcessTransaction::dispatch($transaction->id);

            return $transaction;
        });
    }

    public function getTransactionStatus(string $transactionId): ?Transaction
    {
        return Transaction::where('transaction_id', $transactionId)->first();
    }

    public function getUserBalance(int $userId): string
    {
        // Ensure user has a balance record with default value
        $userBalance = UserBalance::firstOrCreate(
            ['user_id' => $userId],
            ['balance' => '1000000.00']
        );

        return $userBalance->balance;
    }
}
