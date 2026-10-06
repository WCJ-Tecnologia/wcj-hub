<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enterprises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_enterprise_id')->nullable()->constrained('enterprises')->nullOnDelete();
            $table->string('name');
            $table->string('nickname', 80);
            $table->string('document', 32)->nullable()->unique();
            // Who deactivated and when (banco.md §4). Active = deactivated_at is null.
            $table->dateTime('deactivated_at')->nullable();
            $table->foreignId('deactivated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->datetimes();

            $table->index('deactivated_at');
            $table->index('deactivated_by');
            $table->index('nickname');
        });

        Schema::create('user_has_enterprises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained('enterprises')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->datetimes();

            $table->unique(['enterprise_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_has_enterprises');
        Schema::dropIfExists('enterprises');
    }
};
