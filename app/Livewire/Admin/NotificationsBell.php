<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Domain\Notification\Models\Notification;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Livewire\Component;

class NotificationsBell extends Component
{
    public bool $open = false;

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function markAsRead(string $id): ?RedirectResponse
    {
        /** @var Notification|null $notification */
        $notification = $this->baseQuery()->whereKey($id)->first();

        if ($notification === null) {
            return null;
        }

        if (! $notification->isRead()) {
            $notification->markAsRead();
        }

        if ($notification->action_url) {
            return redirect()->to($notification->action_url);
        }

        return null;
    }

    public function markAllAsRead(): void
    {
        $this->baseQuery()->unread()->update(['read_at' => now()]);
    }

    public function getUnreadCountProperty(): int
    {
        return $this->baseQuery()->unread()->count();
    }

    /** @return Collection<int, Notification> */
    public function getRecentProperty(): Collection
    {
        return $this->baseQuery()
            ->orderByRaw('read_at IS NULL DESC')
            ->latest()
            ->limit(10)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.admin.notifications-bell', [
            'unreadCount' => $this->unreadCount,
            'notifications' => $this->recent,
            'locale' => app()->getLocale(),
        ]);
    }

    /**
     * Super admins are not tenant-scoped, so bypass the tenant global scope
     * and filter directly by the authenticated user.
     */
    private function baseQuery(): Builder
    {
        return Notification::query()
            ->withoutGlobalScope('tenant')
            ->where('user_id', auth()->id());
    }
}
