<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_receipt_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('activity_title');
            $table->string('item_name');
            $table->string('category')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_cost', 12, 2);
            $table->date('expense_date');
            $table->string('receipt_path');
            $table->string('receipt_name');
            $table->unsignedTinyInteger('ocr_confidence')->nullable();
            $table->boolean('student_confirmed')->default(false);
            $table->string('verification_status', 24)->default('ready_for_review');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_receipt_reviews');
    }
};
