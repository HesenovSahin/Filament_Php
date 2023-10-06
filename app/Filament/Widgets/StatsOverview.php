<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static ?string $pollingInterval = '15s';

    //in this method we can deactivate the lazy laoding
    protected static bool $isLazy = true;


    protected function getStats(): array
    {
        return [
            Stat::make('Total Customers',Customer::count())
            ->description('Increase in customers')
            ->descriptionIcon('heroicon-m-arrow-trending-up')
            ->color('success')
            ->chart([7,3,5,2,6,8,9,0,]),

            Stat::make('Total Products', Product::count())
            ->description('Total products in app')
            ->descriptionIcon('heroicon-m-arrow-trending-down')
            ->color('danger')
            ->chart([7,3,5,2,6,8,9,0,]),

            Stat::make('Pending Orders', Order::where('status',OrderStatus::PENDING->value)->count())
            ->description('Total products in app')
            ->descriptionIcon('heroicon-m-arrow-trending-down')
            ->color('danger')
            ->chart([7,3,5,2,6,8,9,0,]),

        ];
    }
}
