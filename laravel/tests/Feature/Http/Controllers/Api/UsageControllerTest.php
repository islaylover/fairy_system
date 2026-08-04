<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UsageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_returns_user_and_global_usage(): void
    {
        config([
            'chatgpt.token_limits.monthly_user_limit_usd' => '1.00000',
            'chatgpt.token_limits.monthly_global_limit_usd' => '10.00000',
        ]);

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->createMonthlyUsage($user->id, '0.10000', 100);
        $this->createMonthlyUsage($otherUser->id, '0.20000', 200);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/usage/monthly?year_month='.now()->format('Y-m'));

        $response
            ->assertOk()
            ->assertJsonPath('user.estimated_cost_usd', '0.10000')
            ->assertJsonPath('user.total_tokens', 100)
            ->assertJsonPath('user_all.estimated_cost_usd', '0.30000')
            ->assertJsonPath('user_all.total_tokens', 300);
    }

    private function createMonthlyUsage(int $userId, string $estimatedCostUsd, int $totalTokens): void
    {
        DB::table('monthly_usages')->insert([
            'user_id' => $userId,
            'year_month' => now()->format('Y-m'),
            'prompt_tokens' => $totalTokens,
            'completion_tokens' => 0,
            'total_tokens' => $totalTokens,
            'estimated_cost_usd' => $estimatedCostUsd,
            'requests_done_count' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
