<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_balance_is_the_initial_balance_plus_income_minus_expenses(): void
    {
        $user = $this->signIn();
        $account = Account::factory()->for($user)->create(['initial_balance' => '100.00']);
        Transaction::factory()->for($user)->for($account)->for(Category::factory()->for($user)->income())
            ->create(['type' => 'income', 'amount' => '50.00', 'amount_brl' => '50.00']);
        Transaction::factory()->for($user)->for($account)
            ->create(['type' => 'expense', 'amount' => '6.00', 'currency' => 'USD', 'amount_brl' => '30.25']);

        $response = $this->getJson('/api/v1/accounts');

        $response->assertOk()->assertJsonPath('data.0.balance', '119.75');
    }

    public function test_another_users_account_is_indistinguishable_from_a_missing_one(): void
    {
        $this->signIn();
        $foreign = Account::factory()->create();

        $foreignResponse = $this->patchJson("/api/v1/accounts/{$foreign->id}", ['name' => 'Mine now']);
        $missingResponse = $this->patchJson('/api/v1/accounts/999999', ['name' => 'Mine now']);

        $foreignResponse->assertNotFound();
        $this->assertSame($missingResponse->json(), $foreignResponse->json());
        $this->assertDatabaseMissing('accounts', ['name' => 'Mine now']);
    }

    public function test_an_account_with_transactions_cannot_be_deleted(): void
    {
        $user = $this->signIn();
        $transaction = Transaction::factory()->for($user)->create();

        $response = $this->deleteJson("/api/v1/accounts/{$transaction->account_id}");

        $response->assertConflict();
        $this->assertDatabaseHas('accounts', ['id' => $transaction->account_id]);
    }
}
