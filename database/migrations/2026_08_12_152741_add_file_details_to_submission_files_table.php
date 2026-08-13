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
        Schema::table('submission_files', function (Blueprint $table) {
            // Tambahkan kolom baru setelah file_url
            $table->string('file_name')->after('file_url')->nullable();
            $table->string('mime_type')->after('file_name')->nullable();
            $table->unsignedBigInteger('size')->after('mime_type')->nullable();
            $table->renameColumn('file_url', 'file_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submission_files', function (Blueprint $table) {
            $table->dropColumn(['file_name', 'mime_type', 'size']);
            $table->renameColumn('file_path', 'file_url');
        });
    }
};
