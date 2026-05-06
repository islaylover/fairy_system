<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('model'); // e.g. gpt-3.5-turbo or gpt-4o
            $table->string('request_type'); // e.g. summary, translate, format_table
            $table->text('source_text'); // 入力文
            $table->longText('result_text')->nullable(); // 出力結果（nullable）

            $table->string('status')->default('pending'); // pending, processing, done, failed

            $table->integer('prompt_tokens')->nullable(); // API使用量（入力）
            $table->integer('completion_tokens')->nullable(); // API使用量（出力）
            $table->integer('total_tokens')->nullable(); // 合計トークン数
            $table->decimal('estimated_cost_usd', 8, 5)->nullable(); // e.g. 0.01500
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
