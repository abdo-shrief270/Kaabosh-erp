<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Payroll\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Employee */
class EmployeeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'hire_date' => $this->hire_date?->toDateString(),
            'department' => $this->department,
            'job_title' => $this->job_title,
            'base_salary' => $this->base_salary,
            'social_insurance_number' => $this->social_insurance_number,
            'bank_account' => $this->bank_account,
            'is_insured' => $this->is_insured,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
        ];
    }
}
