<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('modules', 'bibliographie')) {
            Schema::table('modules', function (Blueprint $table) {
                $table->dropColumn('bibliographie');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('modules', 'bibliographie')) {
            Schema::table('modules', function (Blueprint $table) {
                $table->text('bibliographie')->nullable();
            });
        }
    }
};
