<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->table('expense_receipt_reviews', function (Blueprint $table) {
            $table->string('receipt_disk', 20)->default('public');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->table('expense_receipt_reviews', fn (Blueprint $table) => $table->dropColumn('receipt_disk'));
    }
};
