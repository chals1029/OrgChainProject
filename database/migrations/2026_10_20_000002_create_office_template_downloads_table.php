<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        Schema::connection($this->connection)->create('office_template_downloads', function (Blueprint $table): void {
            $table->string('template_key', 160)->primary();
            $table->unsignedBigInteger('downloads')->default(0);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('office_template_downloads');
    }
};
