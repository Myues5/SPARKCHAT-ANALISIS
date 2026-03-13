<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_responses', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('agent_name');
            $table->date('date');
            $table->time('first_response_time');
            $table->time('average_response_time');
            $table->time('resolved_time');
            $table->timestamps();
            
            $table->index(['agent_name', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_responses');
    }
};
