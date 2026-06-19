<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_foto', function (Blueprint $table) {
            $table->string('enhanced_url')->nullable()->after('url');
            $table->string('enhanced_public_id')->nullable()->after('enhanced_url');
            $table->integer('brightness')->default(0)->after('enhanced_public_id');
            $table->integer('contrast')->default(0)->after('brightness');
            $table->integer('sharpness')->default(0)->after('contrast');
            $table->boolean('is_enhanced')->default(false)->after('sharpness');
            $table->foreignId('enhanced_by')->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->after('is_enhanced');
            $table->timestamp('enhanced_at')->nullable()->after('enhanced_by');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_foto', function (Blueprint $table) {
            $table->dropForeign(['enhanced_by']);
            $table->dropColumn([
                'enhanced_url',
                'enhanced_public_id',
                'brightness',
                'contrast',
                'sharpness',
                'is_enhanced',
                'enhanced_by',
                'enhanced_at',
            ]);
        });
    }
};