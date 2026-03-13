<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('labels', function (Blueprint $table) {
            $table->id();
            $table->string('label_name', 100)->index();
            $table->string('created_by', 36);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable();
        });

        // Index seperti yang diminta user
        Schema::table('labels', function (Blueprint $table) {
            $table->index('created_by', 'idx_labels_created_by');
        });
    }

    public function down()
    {
        Schema::dropIfExists('labels');
    }
};

