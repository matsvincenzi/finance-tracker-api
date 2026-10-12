<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListTransactionsRequest;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;

final class TransactionController extends Controller
{
    public function __construct(private readonly TransactionService $transactions) {}

    /**
     * List transactions, newest first, with filters and pagination.
     */
    public function index(ListTransactionsRequest $request, #[CurrentUser] User $user): AnonymousResourceCollection
    {
        $transactions = $user->transactions()
            ->with(['account', 'category'])
            ->filter($request->validated())
            ->sorted($request->string('sort', '-occurred_on')->value())
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return TransactionResource::collection($transactions);
    }

    /**
     * Record a transaction. Amounts in a foreign currency are converted to BRL using the
     * exchange rate of `occurred_on`; the API answers 503 when no rate can be fetched.
     */
    public function store(StoreTransactionRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $transaction = $this->transactions->create($user, $request->validated());

        return (new TransactionResource($transaction))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    #[Authorize('view', 'transaction')]
    public function show(Transaction $transaction): TransactionResource
    {
        return new TransactionResource($transaction->load(['account', 'category']));
    }

    /**
     * Update a transaction. Changing the amount, currency or date fetches the exchange rate again.
     */
    #[Authorize('update', 'transaction')]
    public function update(UpdateTransactionRequest $request, Transaction $transaction): TransactionResource
    {
        return new TransactionResource($this->transactions->update($transaction, $request->validated()));
    }

    #[Authorize('delete', 'transaction')]
    public function destroy(Transaction $transaction): Response
    {
        $transaction->delete();

        return response()->noContent();
    }
}
