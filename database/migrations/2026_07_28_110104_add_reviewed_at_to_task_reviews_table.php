<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('task_reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('task_reviews', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('status');
            }
        });
    }

    public function down()
    {
        Schema::table('task_reviews', function (Blueprint $table) {
            $table->dropColumn('reviewed_at');
        });
    }
};
