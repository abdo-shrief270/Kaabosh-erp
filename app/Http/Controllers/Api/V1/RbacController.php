<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Exposes role-based access control metadata for the frontend's team
 * management UI — specifically the "Apply preset" dropdown that seeds a
 * new team member with a canonical permission set.
 */
class RbacController extends Controller
{
    public function rolePresets(): JsonResponse
    {
        $presets = config('permissions', []);

        $labels = [
            'admin' => ['en' => 'Administrator', 'ar' => 'مدير'],
            'accountant' => ['en' => 'Accountant', 'ar' => 'محاسب'],
            'auditor' => ['en' => 'Auditor', 'ar' => 'مراجع'],
        ];

        $data = [];
        foreach ($presets as $role => $permissions) {
            $data[] = [
                'role' => $role,
                'label' => $labels[$role]['en'] ?? ucfirst($role),
                'label_ar' => $labels[$role]['ar'] ?? $role,
                'permissions' => array_values($permissions),
            ];
        }

        return response()->json(['data' => $data]);
    }
}
