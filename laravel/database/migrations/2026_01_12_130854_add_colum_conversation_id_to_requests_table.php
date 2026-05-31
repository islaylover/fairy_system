<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->unsignedBigInteger('conversation_id')
                ->default(0)
                ->after('user_id')
                ->index('idx_requests_conversation_id');
        });

        DB::statement('UPDATE requests SET conversation_id = id WHERE conversation_id = 0');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropIndex(['idx_requests_conversation_id']);
            $table->dropColumn('conversation_id');
        });
    }
};
