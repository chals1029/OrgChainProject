<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->create('receipt_scans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('uploaded_by')->index();
            $table->string('file_hash', 64)->index();
            $table->string('engine', 80);
            $table->text('raw_text');
            $table->json('extracted');
            $table->unsignedTinyInteger('confidence');
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
        Schema::connection('mysql')->table('expense_receipt_reviews', function (Blueprint $table) {
            $table->foreignUuid('receipt_scan_id')->nullable()->constrained('receipt_scans')->nullOnDelete();
            $table->string('receipt_type', 30)->nullable();
            $table->string('payment_method', 30)->nullable();
            $table->json('ocr_corrections')->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->table('expense_receipt_reviews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('receipt_scan_id');
            $table->dropColumn(['receipt_type', 'payment_method', 'ocr_corrections']);
        });
        Schema::connection('mysql')->dropIfExists('receipt_scans');
    }
};
