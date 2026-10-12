<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;

final class AccountController extends Controller
{
    /**
     * List accounts with their current balance.
     */
    public function index(#[CurrentUser] User $user): AnonymousResourceCollection
    {
        return AccountResource::collection($user->accounts()->withBalance()->orderBy('name')->get());
    }

    public function store(StoreAccountRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $account = $user->accounts()->create($request->validated());

        return $this->withBalance($account)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    #[Authorize('view', 'account')]
    public function show(Account $account): AccountResource
    {
        return $this->withBalance($account);
    }

    #[Authorize('update', 'account')]
    public function update(UpdateAccountRequest $request, Account $account): AccountResource
    {
        $account->update($request->validated());

        return $this->withBalance($account);
    }

    /**
     * Delete an account. Accounts that still have transactions are rejected with 409.
     */
    #[Authorize('delete', 'account')]
    public function destroy(Account $account): Response
    {
        abort_if(
            $account->transactions()->exists(),
            Response::HTTP_CONFLICT,
            'This account has transactions. Delete them before deleting the account.',
        );

        $account->delete();

        return response()->noContent();
    }

    private function withBalance(Account $account): AccountResource
    {
        return new AccountResource(Account::query()->withBalance()->findOrFail($account->id));
    }
}
