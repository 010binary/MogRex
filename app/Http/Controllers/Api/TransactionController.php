<?php
// app/Http/Controllers/Api/TransactionController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
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

    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:pending,processed,failed',
            'type' => 'nullable|in:credit,debit',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'sort_by' => 'nullable|in:created_at,amount,status',
            'sort_order' => 'nullable|in:asc,desc'
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
            $perPage = $request->get('per_page', 15);
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            // Build query for user's transactions
            $query = Transaction::where('user_id', $user->id);

            // Apply filters
            if ($request->has('status'))
            {
                $query->where('status', $request->status);
            }

            if ($request->has('type'))
            {
                $query->where('type', $request->type);
            }

            if ($request->has('from_date'))
            {
                $query->whereDate('created_at', '>=', $request->from_date);
            }

            if ($request->has('to_date'))
            {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            // Apply sorting
            $query->orderBy($sortBy, $sortOrder);

            // Paginate results
            $transactions = $query->paginate($perPage);

            // Format the response
            $formattedTransactions = $transactions->getCollection()->map(function ($transaction) {
                return [
                    'transaction_id' => $transaction->transaction_id,
                    'amount' => number_format($transaction->amount, 2),
                    'type' => $transaction->type,
                    'status' => $transaction->status,
                    'previous_balance' => $transaction->previous_balance ? number_format($transaction->previous_balance, 2) : null,
                    'current_balance' => $transaction->current_balance ? number_format($transaction->current_balance, 2) : null,
                    'failure_reason' => $transaction->failure_reason,
                    'created_at' => $transaction->created_at,
                    'updated_at' => $transaction->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedTransactions,
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                    'last_page' => $transactions->lastPage(),
                    'from' => $transactions->firstItem(),
                    'to' => $transactions->lastItem(),
                ],
                'filters_applied' => [
                    'status' => $request->status,
                    'type' => $request->type,
                    'from_date' => $request->from_date,
                    'to_date' => $request->to_date,
                    'sort_by' => $sortBy,
                    'sort_order' => $sortOrder,
                ]
            ]);
        } catch (\Exception $e)
        {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve transactions',
                'error' => $e->getMessage()
            ], 500);
        }
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
