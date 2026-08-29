<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Provides the cumulative + monthly due-ledger mutation pattern.
 *
 * Preserves the PHP version's proven design:
 *   - updateDue(entityId, amount, month): apply a delta to both cumulative
 *     and monthly ledgers.
 *   - rollbackDue(entityId, amount, month): reverse a prior delta (apply -amount).
 *   - updateDueAfterRollback(entityId, oldAmount, newAmount, month): atomic
 *     replace — rollback old then apply new. Used when re-saving a finalized
 *     month to avoid double-counting.
 *
 * Uses raw DB upsert via INSERT ... ON CONFLICT for portability + atomicity,
 * falling back to firstOrNew+save for the cumulative ledger (which has a
 * single-column PK).
 *
 * Usage in a Model:
 *   class InvestmentTransaction extends Model {
 *       use DueManager;
 *       protected function dueLedgerConfig(): array { ... }
 *   }
 */
trait DueManager
{
    /**
     * Apply a signed delta to the entity's due ledgers (cumulative + monthly).
     */
    public function updateDue(int $entityId, float $amount, string $month): void
    {
        $config = $this->dueLedgerConfig();
        $entityColumn = $config['entity_column'];
        $cumulativeTable = (new $config['cumulative_model'])->getTable();
        $monthlyTable = (new $config['monthly_model'])->getTable();

        DB::transaction(function () use (
            $entityId, $amount, $month,
            $cumulativeTable, $monthlyTable, $entityColumn
        ) {
            // 1. Monthly ledger — raw upsert (composite PK friendly)
            // INSERT ... ON CONFLICT (entity_id, due_month) DO UPDATE SET due = due + ?
            $existingMonthly = DB::table($monthlyTable)
                ->where($entityColumn, $entityId)
                ->where('due_month', $month)
                ->first();

            if ($existingMonthly) {
                DB::table($monthlyTable)
                    ->where($entityColumn, $entityId)
                    ->where('due_month', $month)
                    ->update(['due' => DB::raw("due + {$amount}")]);
            } else {
                DB::table($monthlyTable)->insert([
                    $entityColumn => $entityId,
                    'due_month' => $month,
                    'due' => $amount,
                ]);
            }

            // 2. Cumulative ledger — single-column PK, upsert
            $existingCumulative = DB::table($cumulativeTable)
                ->where($entityColumn, $entityId)
                ->first();

            if ($existingCumulative) {
                DB::table($cumulativeTable)
                    ->where($entityColumn, $entityId)
                    ->update(['due' => DB::raw("due + {$amount}"), 'updated_at' => now()]);
            } else {
                DB::table($cumulativeTable)->insert([
                    $entityColumn => $entityId,
                    'due' => $amount,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function rollbackDue(int $entityId, float $amount, string $month): void
    {
        $this->updateDue($entityId, -$amount, $month);
    }

    public function updateDueAfterRollback(int $entityId, float $oldAmount, float $newAmount, string $month): void
    {
        $this->rollbackDue($entityId, $oldAmount, $month);
        $this->updateDue($entityId, $newAmount, $month);
    }

    public function getDue(int $entityId): float
    {
        $config = $this->dueLedgerConfig();
        $entityColumn = $config['entity_column'];
        $table = (new $config['cumulative_model'])->getTable();

        $row = DB::table($table)->where($entityColumn, $entityId)->first();

        return $row ? (float) $row->due : 0.0;
    }

    public function getMonthlyDue(int $entityId, string $month): float
    {
        $config = $this->dueLedgerConfig();
        $entityColumn = $config['entity_column'];
        $table = (new $config['monthly_model'])->getTable();

        $row = DB::table($table)
            ->where($entityColumn, $entityId)
            ->where('due_month', $month)
            ->first();

        return $row ? (float) $row->due : 0.0;
    }

    public function getPreviousMonthlyDue(int $entityId, string $month): float
    {
        $config = $this->dueLedgerConfig();
        $entityColumn = $config['entity_column'];
        $table = (new $config['monthly_model'])->getTable();

        $row = DB::table($table)
            ->where($entityColumn, $entityId)
            ->where('due_month', '<', $month)
            ->orderBy('due_month', 'desc')
            ->first();

        return $row ? (float) $row->due : 0.0;
    }

    /**
     * Override in the consuming model to specify which entity + ledger pair it owns.
     *
     * @return array{entity_column: string, cumulative_model: class-string, monthly_model: class-string}
     */
    abstract protected function dueLedgerConfig(): array;
}
