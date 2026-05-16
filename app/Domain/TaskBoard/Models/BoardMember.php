<?php

declare(strict_types=1);

namespace App\Domain\TaskBoard\Models;

use App\Domain\Shared\Traits\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardMember extends Model
{
    use BelongsToTenant;

    public const LEVEL_VIEWER = 'viewer';
    public const LEVEL_EDITOR = 'editor';
    public const LEVEL_ADMIN  = 'admin';
    public const LEVELS = [self::LEVEL_VIEWER, self::LEVEL_EDITOR, self::LEVEL_ADMIN];

    // Ordered weakest → strongest; used by BoardAccessService::has().
    public const LEVEL_ORDER = [
        self::LEVEL_VIEWER => 1,
        self::LEVEL_EDITOR => 2,
        self::LEVEL_ADMIN  => 3,
    ];

    protected $fillable = ['tenant_id', 'board_id', 'user_id', 'level'];

    public function board(): BelongsTo { return $this->belongsTo(Board::class); }
    public function user(): BelongsTo  { return $this->belongsTo(User::class); }
}
