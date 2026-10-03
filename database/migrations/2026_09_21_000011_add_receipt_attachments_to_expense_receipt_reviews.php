<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        if (! Schema::connection('mysql')->hasTable('expense_receipt_reviews')) {
            return;
        }

        Schema::connection('mysql')->table('expense_receipt_reviews', function (Blueprint $table) {
            if (! Schema::connection('mysql')->hasColumn('expense_receipt_reviews', 'receipt_attachments')) {
                $table->json('receipt_attachments')->nullable()->after('receipt_name');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::connection('mysql')->hasTable('expense_receipt_reviews')) {
            return;
        }

        Schema::connection('mysql')->table('expense_receipt_reviews', function (Blueprint $table) {
            if (Schema::connection('mysql')->hasColumn('expense_receipt_reviews', 'receipt_attachments')) {
                $table->dropColumn('receipt_attachments');
            }
        });
    }
};
