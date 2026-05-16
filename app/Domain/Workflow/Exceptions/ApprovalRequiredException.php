<?php

declare(strict_types=1);

namespace App\Domain\Workflow\Exceptions;

use Illuminate\Validation\ValidationException;

/**
 * Thrown by domain services when an action is blocked by an active approval
 * workflow. Carries the entity_type/id/amount so the SPA can immediately
 * offer a "Request approval" affordance without inferring from the URL.
 *
 * The exception handler renders this as a 422 with a structured `approval`
 * block alongside the standard `errors` payload — backward-compatible for
 * existing clients but actionable for the SPA.
 */
class ApprovalRequiredException extends ValidationException
{
    public string $entityType;
    public int    $entityId;
    public ?float $amount;

    public static function for(
        string $entityType,
        int $entityId,
        string $message,
        ?float $amount = null,
        string $errorKey = 'approval',
    ): self {
        // Empty rules so Laravel doesn't auto-generate a second message —
        // we want exactly the one we add below.
        $validator = \Illuminate\Support\Facades\Validator::make([], []);
        $validator->errors()->add($errorKey, $message);

        $e = new self($validator);
        $e->entityType = $entityType;
        $e->entityId   = $entityId;
        $e->amount     = $amount;

        return $e;
    }
}
