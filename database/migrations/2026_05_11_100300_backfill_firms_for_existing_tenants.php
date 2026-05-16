<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Wraps every pre-existing tenant in a new Firm row, marks the tenant as that
 * firm's own books (type = firm_books), and assigns the tenant's users to the
 * same firm. Each pre-existing tenant becomes a one-tenant firm; the firm can
 * then add client-tenants from the UI.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $tenants = DB::table('tenants')->whereNull('firm_id')->get();

            foreach ($tenants as $tenant) {
                $firmId = DB::table('firms')->insertGetId([
                    'name'                => $tenant->name,
                    'slug'                => $this->uniqueSlug($tenant->slug),
                    'email'               => $tenant->email,
                    'phone'               => $tenant->phone,
                    'tax_id'              => $tenant->tax_id,
                    'commercial_register' => $tenant->commercial_register,
                    'address'             => $tenant->address,
                    'city'                => $tenant->city,
                    'status'              => 'active',
                    'subscription_tier'   => 'firm_starter',
                    'settings'            => '{}',
                    'created_at'          => $tenant->created_at,
                    'updated_at'          => now(),
                ]);

                DB::table('tenants')->where('id', $tenant->id)->update([
                    'firm_id' => $firmId,
                    'type'    => 'firm_books',
                ]);

                DB::table('users')
                    ->where('tenant_id', $tenant->id)
                    ->update(['firm_id' => $firmId, 'firm_role' => 'owner']);
            }
        });
    }

    public function down(): void
    {
        DB::table('users')->update(['firm_id' => null, 'firm_role' => null]);
        DB::table('tenants')->update(['firm_id' => null]);
        DB::table('firms')->truncate();
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base ?: 'firm-' . Str::random(8);
        $i = 0;
        while (DB::table('firms')->where('slug', $slug)->exists()) {
            $i++;
            $slug = $base . '-' . $i;
        }
        return $slug;
    }
};
