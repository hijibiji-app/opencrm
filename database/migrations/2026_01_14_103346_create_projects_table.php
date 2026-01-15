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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('platform'); // Laravel, WordPress, etc.
            $table->string('category'); // LMS, Ecommerce, Single Vendor, Multi Vendor, etc.
            $table->string('domain')->nullable(); // Project URL/domain
            $table->string('status')->default('active'); // active, inactive, maintenance
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Creator
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
