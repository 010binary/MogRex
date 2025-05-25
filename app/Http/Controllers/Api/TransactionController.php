<?php
// app/Http/Controllers/Api/TransactionController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    public function __construct(
        private TransactionService $transactionService
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01|max:999999999.99',
            'type' => 'required|in:credit,debit',
        ]);

        if ($validator->fails())
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try
        {
            $user = $request->user();
            $idempotencyKey = $request->header('Idempotency-Key');

            $transaction = $this->transactionService->createTransaction(
                $user->id,
                $request->amount,
                $request->type,
                $idempotencyKey
            );

            return response()->json([
                'success' => true,
                'transaction_id' => $transaction->transaction_id,
                'message' => 'Transaction queued for processing',
                'previous_balance' => number_format($transaction->previous_balance, 2),
                'current_balance' => number_format($transaction->current_balance, 2),
                'status' => $transaction->status
            ], 201);
        } catch (\Exception $e)
        {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, string $transactionId): JsonResponse
    {
        $transaction = $this->transactionService->getTransactionStatus($transactionId);

        if (! $transaction)
        {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        // Ensure user can only see their own transactions
        if ($transaction->user_id !== $request->user()->id)
        {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'transaction' => [
                'transaction_id' => $transaction->transaction_id,
                'amount' => number_format($transaction->amount, 2),
                'type' => $transaction->type,
                'status' => $transaction->status,
                'previous_balance' => $transaction->previous_balance ? number_format($transaction->previous_balance, 2) : null,
                'current_balance' => $transaction->current_balance ? number_format($transaction->current_balance, 2) : null,
                'failure_reason' => $transaction->failure_reason,
                'created_at' => $transaction->created_at,
                'updated_at' => $transaction->updated_at,
            ]
        ]);
    }

    public function balance(Request $request): JsonResponse
    {
        $balance = $this->transactionService->getUserBalance($request->user()->id);

        return response()->json([
            'success' => true,
            'balance' => number_format($balance, 2),
            'user_id' => $request->user()->id
        ]);
    }
}
