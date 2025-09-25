<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Clean up any orphaned messages first to satisfy FK constraint
        try {
            DB::statement('DELETE FROM quote_messages WHERE quote_id NOT IN (SELECT id FROM quotes)');
        } catch (\Throwable $e) {
            // no-op: table might be empty on fresh setups
        }

        // Ensure index exists for performance (safe even if duplicate in most MySQL setups)
        try {
            Schema::table('quote_messages', function (Blueprint $table) {
                $table->index('quote_id', 'quote_messages_quote_id_index');
            });
        } catch (\Throwable $e) {
            // index may already exist; ignore
        }

        // Add FK with cascade to prevent orphan rows and historical leak-ins when IDs are reused
        try {
            Schema::table('quote_messages', function (Blueprint $table) {
                $table->foreign('quote_id', 'quote_messages_quote_id_foreign')
                    ->references('id')->on('quotes')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
            });
        } catch (\Throwable $e) {
            // FK may already exist; ignore
        }
    }

    public function down(): void
    {
        try {
            Schema::table('quote_messages', function (Blueprint $table) {
                $table->dropForeign('quote_messages_quote_id_foreign');
            });
        } catch (\Throwable $e) {
            // ignore if FK doesn't exist
        }

        try {
            Schema::table('quote_messages', function (Blueprint $table) {
                $table->dropIndex('quote_messages_quote_id_index');
            });
        } catch (\Throwable $e) {
            // ignore if index doesn't exist
        }
    }
};
