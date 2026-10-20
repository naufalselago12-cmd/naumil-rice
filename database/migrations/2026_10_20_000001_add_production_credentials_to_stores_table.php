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
        Schema::table('stores', function (Blueprint $table) {
            if (!Schema::hasColumn('stores', 'mayar_api_key_production')) {
                $table->text('mayar_api_key_production')->nullable()->after('mayar_environment');
            }
            if (!Schema::hasColumn('stores', 'mayar_webhook_token_production')) {
                $table->string('mayar_webhook_token_production')->nullable()->after('mayar_api_key_production');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('stores', 'mayar_api_key_production')) {
                $columns[] = 'mayar_api_key_production';
            }
            if (Schema::hasColumn('stores', 'mayar_webhook_token_production')) {
                $columns[] = 'mayar_webhook_token_production';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
