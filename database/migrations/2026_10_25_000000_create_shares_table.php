<?php

use Bernskiold\LaravelCollaboration\Collaboration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection(): ?string
    {
        return config('collaboration.connection');
    }

    public function up(): void
    {
        Schema::create(config('collaboration.table', 'shares'), function (Blueprint $table) {
            $table->id();
            $table->morphs('shareable');
            $table->foreignId('shared_with_id')->constrained(Collaboration::userTable())->cascadeOnDelete();
            $table->foreignId('shared_by_id')->nullable()->constrained(Collaboration::userTable())->nullOnDelete();
            $table->string('permission')->default('view');
            $table->timestamps();

            $table->unique(['shareable_type', 'shareable_id', 'shared_with_id']);
            $table->index('shared_with_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('collaboration.table', 'shares'));
    }
};
