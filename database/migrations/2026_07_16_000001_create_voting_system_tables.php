<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('voting')->hasTable('admin_users')) {
            Schema::connection('voting')->create('admin_users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password_hash');
                $table->enum('role', ['admin', 'canvassing', 'view_only']);
                $table->boolean('is_active')->default(true);
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::connection('voting')->hasTable('elections')) {
            Schema::connection('voting')->create('elections', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->enum('status', ['pending', 'open', 'closed'])->default('pending');
                $table->dateTime('start_at')->nullable();
                $table->dateTime('end_at')->nullable();
                $table->text('instructions')->nullable();
                $table->text('announcement')->nullable();
                $table->dateTime('announcement_expires_at')->nullable();
                $table->string('ballot_card_kicker')->nullable();
                $table->string('ballot_card_heading', 512)->nullable();
                $table->text('ballot_card_body')->nullable();
                $table->string('ballot_card_image_path', 512)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::connection('voting')->hasTable('voters')) {
            Schema::connection('voting')->create('voters', function (Blueprint $table) {
                $table->id();
                $table->string('sr_code', 100)->unique();
                $table->string('email');
                $table->string('full_name');
                $table->string('college');
                $table->string('program')->nullable();
                $table->string('year_level', 50)->nullable();
                $table->string('grade_level', 50)->nullable();
                $table->boolean('has_voted')->default(false);
                $table->dateTime('voted_at')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::connection('voting')->hasTable('positions')) {
            Schema::connection('voting')->create('positions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('election_id')->constrained('elections')->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->enum('selection_type', ['radio', 'checkbox'])->default('radio');
                $table->integer('max_choices')->default(1);
                $table->integer('sort_order')->default(0);
            });
        }

        if (! Schema::connection('voting')->hasTable('candidates')) {
            Schema::connection('voting')->create('candidates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('position_id')->constrained('positions')->cascadeOnDelete();
                $table->string('name');
                $table->string('party')->nullable();
                $table->string('image_path')->nullable();
                $table->binary('image_blob')->nullable();
                $table->string('image_mime', 100)->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::connection('voting')->hasTable('vote_receipts')) {
            Schema::connection('voting')->create('vote_receipts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('election_id')->constrained('elections')->cascadeOnDelete();
                $table->foreignId('voter_id')->constrained('voters')->cascadeOnDelete();
                $table->string('reference_code')->unique();
                $table->dateTime('created_at');
                $table->unique(['election_id', 'voter_id'], 'uniq_vote_receipts_election_voter');
            });
        }

        if (! Schema::connection('voting')->hasTable('votes')) {
            Schema::connection('voting')->create('votes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('election_id')->constrained('elections')->cascadeOnDelete();
                $table->foreignId('voter_id')->constrained('voters')->cascadeOnDelete();
                $table->foreignId('position_id')->constrained('positions')->cascadeOnDelete();
                $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
                $table->dateTime('created_at');
                $table->unique(['election_id', 'voter_id', 'position_id', 'candidate_id'], 'uniq_votes_choice');
            });
        }

        if (! Schema::connection('voting')->hasTable('audit_logs')) {
            Schema::connection('voting')->create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('admin_users')->nullOnDelete();
                $table->string('actor_name');
                $table->string('action');
                $table->text('details')->nullable();
                $table->dateTime('created_at');
            });
        }

        if (! Schema::connection('voting')->hasTable('security_events')) {
            Schema::connection('voting')->create('security_events', function (Blueprint $table) {
                $table->id();
                $table->string('ip_address', 45);
                $table->string('user_agent', 512)->nullable();
                $table->string('method', 10)->nullable();
                $table->string('path');
                $table->string('event_type', 100);
                $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
                $table->integer('request_count')->default(0);
                $table->text('details')->nullable();
                $table->dateTime('created_at');
                $table->index('created_at', 'idx_security_events_created');
                $table->index(['ip_address', 'created_at'], 'idx_security_events_ip_created');
                $table->index(['event_type', 'created_at'], 'idx_security_events_type_created');
            });
        }
    }

    public function down(): void
    {
        Schema::connection('voting')->dropIfExists('security_events');
        Schema::connection('voting')->dropIfExists('audit_logs');
        Schema::connection('voting')->dropIfExists('votes');
        Schema::connection('voting')->dropIfExists('vote_receipts');
        Schema::connection('voting')->dropIfExists('candidates');
        Schema::connection('voting')->dropIfExists('positions');
        Schema::connection('voting')->dropIfExists('voters');
        Schema::connection('voting')->dropIfExists('elections');
        Schema::connection('voting')->dropIfExists('admin_users');
    }
};
