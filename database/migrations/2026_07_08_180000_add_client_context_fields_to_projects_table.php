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
        Schema::table('projects', function (Blueprint $table) {
            $table->string('delivery_type')->nullable()->after('project_type');
            $table->string('target_audience')->nullable()->after('delivery_type');
            $table->string('material_size')->nullable()->after('target_audience');
            $table->text('legal_requirements')->nullable()->after('description');
            $table->text('reference_links')->nullable()->after('legal_requirements');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_type',
                'target_audience',
                'material_size',
                'legal_requirements',
                'reference_links',
            ]);
        });
    }
};
