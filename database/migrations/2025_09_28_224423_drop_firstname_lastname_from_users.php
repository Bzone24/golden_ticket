<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ensure full_name exists and is populated from first_name + last_name
        if (! Schema::hasColumn('users', 'full_name')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('full_name')->nullable()->after('last_name');
            });
        }

        // Backfill full_name from first_name + last_name, but only when needed
        if (Schema::hasColumn('users', 'first_name') || Schema::hasColumn('users', 'last_name')) {
            DB::statement("
                UPDATE users
                SET full_name = TRIM(CONCAT(
                    COALESCE(first_name, ''),
                    CASE WHEN first_name IS NULL OR first_name = '' THEN '' ELSE ' ' END,
                    COALESCE(last_name, '')
                ))
                WHERE full_name IS NULL OR full_name = ''
            ");
        }

        // Finally drop the two old columns if they exist
        // Use Schema::hasColumn guards to avoid duplicate-drop errors
        if (Schema::hasColumn('users', 'first_name') || Schema::hasColumn('users', 'last_name')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'first_name')) {
                    $table->dropColumn('first_name');
                }
                if (Schema::hasColumn('users', 'last_name')) {
                    $table->dropColumn('last_name');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * This will recreate first_name and last_name (nullable) and attempt to split
     * full_name back into first_name (first token) and last_name (remainder).
     */
    public function down(): void
    {
        // Add columns back as nullable to avoid insert errors
        if (! Schema::hasColumn('users', 'first_name') || ! Schema::hasColumn('users', 'last_name')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'first_name')) {
                    $table->string('first_name')->nullable()->after('id');
                }
                if (! Schema::hasColumn('users', 'last_name')) {
                    $table->string('last_name')->nullable()->after('first_name');
                }
            });
        }

        // Populate first_name/last_name from full_name when possible
        if (Schema::hasColumn('users', 'full_name')) {
            DB::statement("
                UPDATE users
                SET first_name = TRIM(SUBSTRING_INDEX(full_name, ' ', 1)),
                    last_name = TRIM(
                        CASE
                            WHEN INSTR(full_name, ' ') = 0 THEN ''
                            ELSE SUBSTRING(full_name, INSTR(full_name, ' ') + 1)
                        END
                    )
                WHERE (first_name IS NULL OR first_name = '') OR (last_name IS NULL OR last_name = '')
            ");
        }
    }
};
