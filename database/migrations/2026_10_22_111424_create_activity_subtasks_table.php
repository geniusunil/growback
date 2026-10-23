
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subtask_items', function (Blueprint $table) {
            $table->id();

            // Parent Activity Subtask
            $table->foreignId('subtask_id')
                ->constrained('activity_subtasks')
                ->cascadeOnDelete();

            $table->string('title');
            $table->boolean('is_done')->default(false);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subtask_items');
    }
};
