<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add-on boosts originally targeted per-tenant limits (`max_users`,
 * `max_clients`). Under Model B those same boosts target firm-level limits:
 *   max_users   → max_staff_seats
 *   max_clients → max_client_tenants
 *
 * The other keys (max_storage_bytes, max_invoices_per_month) are unchanged
 * because storage + invoice volume are still per-firm aggregates.
 */
return new class extends Migration
{
    private const RENAMES = [
        'max_users'   => 'max_staff_seats',
        'max_clients' => 'max_client_tenants',
    ];

    public function up(): void
    {
        $rows = DB::table('add_ons')->whereNotNull('boost')->get(['id', 'boost']);

        foreach ($rows as $row) {
            $boost = is_string($row->boost) ? json_decode($row->boost, true) : (array) $row->boost;
            if (! is_array($boost)) continue;

            $changed = false;
            foreach (self::RENAMES as $old => $new) {
                if (array_key_exists($old, $boost) && ! array_key_exists($new, $boost)) {
                    $boost[$new] = $boost[$old];
                    unset($boost[$old]);
                    $changed = true;
                }
            }

            if ($changed) {
                DB::table('add_ons')->where('id', $row->id)->update([
                    'boost'      => json_encode($boost),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $reverse = array_flip(self::RENAMES);
        $rows = DB::table('add_ons')->whereNotNull('boost')->get(['id', 'boost']);

        foreach ($rows as $row) {
            $boost = is_string($row->boost) ? json_decode($row->boost, true) : (array) $row->boost;
            if (! is_array($boost)) continue;

            $changed = false;
            foreach ($reverse as $new => $old) {
                if (array_key_exists($new, $boost) && ! array_key_exists($old, $boost)) {
                    $boost[$old] = $boost[$new];
                    unset($boost[$new]);
                    $changed = true;
                }
            }

            if ($changed) {
                DB::table('add_ons')->where('id', $row->id)->update([
                    'boost'      => json_encode($boost),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
