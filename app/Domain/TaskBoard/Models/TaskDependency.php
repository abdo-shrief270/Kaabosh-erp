<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskDependency extends Model
{
    use BelongsToTenant, HasFactory;

    public const KIND_BLOCKS = 'blocks';
    public const KIND_DUPLICATES = 'duplicates';
    public const KIND_RELATES_TO = 'relates_to';

    public const KINDS = [self::KIND_BLOCKS, self::KIND_DUPLICATES, self::KIND_RELATES_TO];

    protected $fillable = ['tenant_id', 'task_id', 'depends_on_task_id', 'kind', 'created_by'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function dependsOn(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'depends_on_task_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
