<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create("tasks",function (Blueprint $table)   {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string("title");
            $table->text("description");
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled', 'overdue'])
            ->default('pending');
            $table->integer('progress')->default(0);
            $table->date('due_date');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
