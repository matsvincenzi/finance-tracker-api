<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    private const string FRANKFURTER = 'https://api.frankfurter.dev/v1';

    public function test_brl_transaction_takes_its_type_from_the_category_and_skips_the_exchange_api(): void
    {
        $user = $this->signIn();
        $account = Account::factory()->for($user)->create();
        $category = Category::factory()->for($user)->income()->create();

        $response = $this->postJson('/api/v1/transactions', [
            'account_id' => $account->id,
            'category_id' => $category->id,
            'description' => 'Salary',
            'amount' => 5200.5,
            'occurred_on' => '2026-03-05',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'income')
            ->assertJsonPath('data.currency', 'BRL')
            ->assertJsonPath('data.amount', '5200.50')
            ->assertJsonPath('data.amount_brl', '5200.50')
            ->assertJsonPath('data.exchange_rate', '1.000000');
        Http::assertNothingSent();
    }

    public function test_foreign_currency_is_converted_with_the_rate_of_the_transaction_date(): void
    {
        $user = $this->signIn();
        $this->fakeFrankfurter(rate: 5.005);

        $response = $this->postJson('/api/v1/transactions', [
            'account_id' => Account::factory()->for($user)->create()->id,
            'category_id' => Category::factory()->for($user)->create()->id,
            'description' => 'Cloud hosting',
            'amount' => '19.99',
            'currency' => 'USD',
            'occurred_on' => '2026-03-07',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.exchange_rate', '5.005000')
            ->assertJsonPath('data.amount_brl', '100.05');
        Http::assertSent(fn (Request $request) => $request->url() === self::FRANKFURTER.'/2026-03-07?base=USD&symbols=BRL');
    }

    public function test_answers_503_and_stores_nothing_when_exchange_rates_are_unavailable(): void
    {
        Sleep::fake();
        $user = $this->signIn();
        Http::fake([
            self::FRANKFURTER.'/currencies' => Http::response(['USD' => 'United States Dollar']),
            self::FRANKFURTER.'/*' => Http::response(status: 502),
        ]);

        $response = $this->postJson('/api/v1/transactions', [
            'account_id' => Account::factory()->for($user)->create()->id,
            'category_id' => Category::factory()->for($user)->create()->id,
            'description' => 'Cloud hosting',
            'amount' => '19.99',
            'currency' => 'USD',
            'occurred_on' => '2026-03-07',
        ]);

        $response->assertServiceUnavailable()->assertHeader('Retry-After', '60');
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_rejects_accounts_and_categories_of_other_users(): void
    {
        $this->signIn();

        $response = $this->postJson('/api/v1/transactions', [
            'account_id' => Account::factory()->create()->id,
            'category_id' => Category::factory()->create()->id,
            'description' => 'Sneaky',
            'amount' => '10.00',
            'occurred_on' => '2026-03-07',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['account_id', 'category_id']);
    }

    public function test_changing_the_amount_converts_it_again_but_renaming_does_not(): void
    {
        $user = $this->signIn();
        $transaction = Transaction::factory()->for($user)->create([
            'amount' => '10.00',
            'currency' => 'EUR',
            'exchange_rate' => '6.000000',
            'amount_brl' => '60.00',
        ]);
        $this->fakeFrankfurter(rate: 6.5);

        $this->patchJson("/api/v1/transactions/{$transaction->id}", ['description' => 'Renamed'])->assertOk();
        Http::assertNothingSent();

        $this->patchJson("/api/v1/transactions/{$transaction->id}", ['amount' => '20.00'])
            ->assertOk()
            ->assertJsonPath('data.exchange_rate', '6.500000')
            ->assertJsonPath('data.amount_brl', '130.00');
    }

    public function test_filters_by_type_and_inclusive_date_range_with_pagination(): void
    {
        $user = $this->signIn();
        $income = Category::factory()->for($user)->income()->create();
        Transaction::factory()->for($user)->create(['occurred_on' => '2026-03-31']);
        Transaction::factory()->for($user)->create(['occurred_on' => '2026-03-01']);
        Transaction::factory()->for($user)->create(['occurred_on' => '2026-04-01']);
        Transaction::factory()->for($user)->for($income)->create(['type' => 'income', 'occurred_on' => '2026-03-15']);
        Transaction::factory()->create(['occurred_on' => '2026-03-10']);

        $response = $this->getJson('/api/v1/transactions?type=expense&from=2026-03-01&to=2026-03-31&per_page=1');

        $response->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('data.0.occurred_on', '2026-03-31');
    }

    private function fakeFrankfurter(float $rate): void
    {
        Http::fake([
            self::FRANKFURTER.'/currencies' => Http::response(['EUR' => 'Euro', 'USD' => 'United States Dollar']),
            self::FRANKFURTER.'/*' => Http::response(['amount' => 1.0, 'base' => 'EUR', 'date' => '2026-03-06', 'rates' => ['BRL' => $rate]]),
        ]);
    }
}
