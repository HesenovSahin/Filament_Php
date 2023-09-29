<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationGroup = 'Shop';

    // when we are doing this that column will not be visible on the navbar
    // protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status','processing')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('status','processing')->count() > 10
        ? 'warning'
        : 'primary';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Order Details')
                    ->schema([

                        Forms\Components\TextInput::make('numbers')
                        ->default('OR-' . random_int(100000, 999999))
                        ->disabled()
                        ->dehydrated()
                        ->required(),  
                        
                        Forms\Components\Select::make('customer_id')
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->required(),

                        Forms\Components\TextInput::make('shipping_price')
                        ->label('Shipping Costs')
                        ->dehydrated()
                        ->numeric()
                        ->required(),

                        Forms\Components\Select::make('type')
                        ->options([
                            'pending' => OrderStatus::PENDING->value,
                            'processing' => OrderStatus::PROCESSING->value,
                            'completed' => OrderStatus::COMPLETED->value,
                            'declined' => OrderStatus::DECLINED->value
                        ])
                        ->required(),

                        Forms\Components\MarkdownEditor::make('notes')
                        ->columnSpanFull()


                    ])->columns(2),

                    Forms\Components\Wizard\Step::make('Order Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                        ->relationship()
                        ->schema([
                            Forms\Components\Select::make('product_id')
                        ->options(
                            Product::query()->pluck('name', 'id')
                        )
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(fn($state, Forms\Set $set)=>
                         $set('unit_price', Product::find($state)?->price ?? 0))
                        ->label('Product')
                        ->required(),

                        Forms\Components\TextInput::make('quantity')
                        ->numeric()
                        ->default(1)
                        ->live()
                        ->dehydrated()
                        ->required(),     
                        
                        Forms\Components\TextInput::make('unit_price')
                        ->label('Unit Price')
                        ->readOnly()
                        ->dehydrated()
                        ->numeric()
                        ->required(), 
                        
                        Forms\Components\Placeholder::make('total_price')
                        ->label('Total Price')
                        ->content(function ($get){
                            $quantity = intval($get('quantity'));
                            $unitPrice = floatval($get('unit_price'));
                            return $quantity * $unitPrice;
                        }), 

                        ])->columns(4)
                ])

                ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numbers')
                ->searchable()
                ->sortable(),

                Tables\Columns\TextColumn::make('customer.name')
                ->searchable()
                ->sortable()
                ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                ->searchable()
                ->sortable(),   

                Tables\Columns\TextColumn::make('created_at')
                ->label('Order date')
                ->date(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }    
}
