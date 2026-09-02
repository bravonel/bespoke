<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temporary_coverage_scopes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('temporary_coverage_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['temporary_coverage_id', 'client_id']);
            $table->unique(['temporary_coverage_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temporary_coverage_scopes');
    }
};
