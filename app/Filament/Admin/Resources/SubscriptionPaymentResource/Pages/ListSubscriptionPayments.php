<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SubscriptionPaymentResource\Pages;

use App\Domain\Subscription\Enums\PaymentStatus;
use App\Filament\Admin\Resources\SubscriptionPaymentResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSubscriptionPayments extends ListRecords
{
    protected static string $resource = SubscriptionPaymentResource::class;

    /** @return array<string, Tab> */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'dunning' => Tab::make('Dunning')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', PaymentStatus::Failed->value))
                ->badge(fn () => (string) static::getResource()::getEloquentQuery()
                    ->where('status', PaymentStatus::Failed->value)
                    ->count())
                ->badgeColor('danger'),
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', PaymentStatus::Pending->value))
                ->badgeColor('warning'),
            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', PaymentStatus::Completed->value)),
            'refunded' => Tab::make('Refunded')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', PaymentStatus::Refunded->value)),
        ];
    }
}
