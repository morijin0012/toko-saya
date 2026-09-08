<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_archives', function (Blueprint $table) {
            $table->text('deleted_transaction_marker')
                ->nullable()
                ->after('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('backup_archives', function (Blueprint $table) {
            $table->dropColumn('deleted_transaction_marker');
        });
    }
};