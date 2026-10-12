<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_totals_only_the_requested_month_grouped_by_category(): void
    {
        $user = $this->signIn();
        $salary = Category::factory()->for($user)->income()->create(['name' => 'Salary']);
        $rent = Category::factory()->for($user)->create(['name' => 'Rent']);
        $food = Category::factory()->for($user)->create(['name' => 'Food']);
        Transaction::factory()->for($user)->for($salary)->create(['type' => 'income', 'amount_brl' => '5000.00', 'occurred_on' => '2026-03-01']);
        Transaction::factory()->for($user)->for($rent)->create(['amount_brl' => '1500.00', 'occurred_on' => '2026-03-10']);
        Transaction::factory()->for($user)->for($food)->create(['amount_brl' => '300.00', 'occurred_on' => '2026-03-12']);
        Transaction::factory()->for($user)->for($food)->create(['amount_brl' => '200.00', 'occurred_on' => '2026-03-31']);
        Transaction::factory()->for($user)->for($food)->create(['amount_brl' => '999.00', 'occurred_on' => '2026-02-28']);
        Transaction::factory()->for($user)->for($food)->create(['amount_brl' => '999.00', 'occurred_on' => '2026-04-01']);

        $response = $this->getJson('/api/v1/reports/monthly?month=2026-03');

        $response->assertOk()
            ->assertJsonPath('data.income', '5000.00')
            ->assertJsonPath('data.expense', '2000.00')
            ->assertJsonPath('data.balance', '3000.00')
            ->assertJsonPath('data.categories.1.name', 'Rent')
            ->assertJsonPath('data.categories.1.share', 75)
            ->assertJsonPath('data.categories.2.name', 'Food')
            ->assertJsonPath('data.categories.2.total', '500.00')
            ->assertJsonPath('data.categories.2.transactions_count', 2);
    }
}
