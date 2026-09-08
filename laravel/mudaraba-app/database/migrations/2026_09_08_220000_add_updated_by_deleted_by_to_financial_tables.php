<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `updated_by` and `deleted_by` columns to all financial tables that
 * already have `created_by`.
 *
 * The plan §4.1 calls for "Universal created_by, updated_by, deleted_by"
 * on all financial records. The `created_by` column was already present on
 * most tables from the original migrations; this migration adds the missing
 * `updated_by` and `deleted_by` columns.
 *
 * Both columns are nullable foreign keys to `users.id` with `nullOnDelete`
 * (so if a user is deleted, their audit trail references become null rather
 * than cascading the deletion).
 *
 * Tables updated (all have `created_by` already):
 *   - investment_transactions
 *   - sector_investments
 *   - director_transactions
 *   - monthly_sector_profit
 *   - investor_monthly_profit_details
 *   - profit_adjustments
 *   - advance_profit_adjustments
 *   - advance_profit_adjustments_type_a (legacy — may not exist if dropped)
 *   - advance_profit_adjustments_type_b (legacy — may not exist if dropped)
 *   - retained_earnings
 *   - employees
 *
 * Tables NOT updated (correctly — these don't have created_by and don't
 * need updated_by/deleted_by):
 *   - Due ledger tables (running balances, not records)
 *   - Monthly due tables (same)
 *   - audit_logs (append-only, captures user_id separately)
 *   - users, investors, sectors, directors, menus (master data — soft-deletes
 *     handle deletion tracking; created_by not applicable)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tables that already have created_by and should get updated_by + deleted_by
        $tables = [
            'investment_transactions',
            'sector_investments',
            'director_transactions',
            'monthly_sector_profit',
            'investor_monthly_profit_details',
            'profit_adjustments',
            'advance_profit_adjustments',
            'retained_earnings',
            'employees',
        ];

        // Legacy tables — may or may not exist (dropped by later migration)
        $legacyTables = [
            'advance_profit_adjustments_type_a',
            'advance_profit_adjustments_type_b',
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) {
                if (! Schema::hasColumn($table->getTable(), 'updated_by')) {
                    $table->foreignId('updated_by')
                        ->nullable()
                        ->after('created_by')
                        ->constrained('users')
                        ->nullOnDelete();
                }

                if (! Schema::hasColumn($table->getTable(), 'deleted_by')) {
                    // Place deleted_by after updated_by, or after created_by if
                    // updated_by doesn't exist (edge case)
                    $afterColumn = Schema::hasColumn($table->getTable(), 'updated_by')
                        ? 'updated_by'
                        : 'created_by';

                    $table->foreignId('deleted_by')
                        ->nullable()
                        ->after($afterColumn)
                        ->constrained('users')
                        ->nullOnDelete();
                }
            });
        }

        // Legacy tables — only add if they still exist
        foreach ($legacyTables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) {
                if (! Schema::hasColumn($table->getTable(), 'updated_by')) {
                    $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn($table->getTable(), 'deleted_by')) {
                    $table->foreignId('deleted_by')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'investment_transactions',
            'sector_investments',
            'director_transactions',
            'monthly_sector_profit',
            'investor_monthly_profit_details',
            'profit_adjustments',
            'advance_profit_adjustments',
            'retained_earnings',
            'employees',
            'advance_profit_adjustments_type_a',
            'advance_profit_adjustments_type_b',
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) {
                if (Schema::hasColumn($table->getTable(), 'deleted_by')) {
                    $table->dropForeign([$table->getTable() . '_deleted_by_foreign']);
                    $table->dropColumn('deleted_by');
                }
                if (Schema::hasColumn($table->getTable(), 'updated_by')) {
                    $table->dropForeign([$table->getTable() . '_updated_by_foreign']);
                    $table->dropColumn('updated_by');
                }
            });
        }
    }
};
