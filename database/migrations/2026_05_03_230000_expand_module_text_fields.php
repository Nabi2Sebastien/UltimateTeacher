<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE modules MODIFY level TEXT NULL');
            DB::statement('ALTER TABLE modules MODIFY teacher_profile TEXT NULL');
            DB::statement('ALTER TABLE modules MODIFY pedagogical_approach TEXT NULL');
            DB::statement('ALTER TABLE modules MODIFY assessment_type TEXT NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE modules MODIFY level VARCHAR(255) NULL');
            DB::statement('ALTER TABLE modules MODIFY teacher_profile VARCHAR(255) NULL');
            DB::statement('ALTER TABLE modules MODIFY pedagogical_approach VARCHAR(255) NULL');
            DB::statement('ALTER TABLE modules MODIFY assessment_type VARCHAR(255) NULL');
        }
    }
};
