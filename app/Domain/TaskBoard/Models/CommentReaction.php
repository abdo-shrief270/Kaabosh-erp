<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentReaction extends Model
{
    use BelongsToTenant, HasFactory;

    public $timestamps = false;

    protected $fillable = ['tenant_id', 'comment_id', 'user_id', 'emoji', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(TaskComment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
