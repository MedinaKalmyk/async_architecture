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
        Schema::table('task', function ($table) {
            $table->string('title', 80)->after('name')
                ->nullable()
                ->default(null);
            $table->string('jira_id')->after('name')
                ->nullable()
                ->default(null);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
