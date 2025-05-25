<?php
// app/Jobs/ProcessTransaction.php
namespace App\Jobs;

use App\Models\Transaction;
use App\Models\UserBalance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessTransaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3; //Number of attempts before failing
    public int $backoff = 5; //Delay in seconds before retrying the job

    public function __construct(
        private int $transactionId
    ) {
    }

    public function handle(): void
    {
        $transaction = Transaction::find($this->transactionId);

        if (! $transaction || $transaction->status !== 'pending')
        {
            return;
        }

        try
        {
            DB::transaction(function () use ($transaction) {
                // Lock the user balance row to prevent concurrent modifications
                $userBalance = UserBalance::where('user_id', $transaction->user_id)
                    ->lockForUpdate()
                    ->first();

                if (! $userBalance)
                {
                    $userBalance = UserBalance::create([
                        'user_id' => $transaction->user_id,
                        'balance' => '0.00'
                    ]);
                }

                $previousBalance = $userBalance->balance;

                // Calculate new balance
                if ($transaction->type === 'credit')
                {
                    $newBalance = bcadd($previousBalance, $transaction->amount, 2);
                } else
                { // debit
                    $newBalance = bcsub($previousBalance, $transaction->amount, 2);

                    // Check for sufficient funds
                    if (bccomp($newBalance, '0', 2) < 0)
                    {
                        throw new Exception('Insufficient funds');
                    }
                }

                // Update user balance
                $userBalance->update(['balance' => $newBalance]);

                // Update transaction with balances and mark as processed
                $transaction->update([
                    'status' => 'processed',
                    'previous_balance' => $previousBalance,
                    'current_balance' => $newBalance,
                ]);

                Log::info("Transaction {$transaction->transaction_id} processed successfully", [
                    'user_id' => $transaction->user_id,
                    'amount' => $transaction->amount,
                    'type' => $transaction->type,
                    'previous_balance' => $previousBalance,
                    'current_balance' => $newBalance,
                ]);
            });
        } catch (Exception $e)
        {
            // Mark transaction as failed
            $transaction->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
            ]);

            Log::error("Transaction {$transaction->transaction_id} failed", [
                'error' => $e->getMessage(),
                'user_id' => $transaction->user_id,
            ]);
        }
    }

    public function failed(Exception $exception): void
    {
        $transaction = Transaction::find($this->transactionId);

        if ($transaction)
        {
            $transaction->update([
                'status' => 'failed',
                'failure_reason' => 'Job failed after maximum retries: '.$exception->getMessage(),
            ]);
        }

        Log::error("ProcessTransaction job failed permanently", [
            'transaction_id' => $this->transactionId,
            'error' => $exception->getMessage(),
        ]);
    }
}
