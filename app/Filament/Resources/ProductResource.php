<?php

namespace App\Filament\Resources;

use App\Enums\ProductType;
use Illuminate\Support\Str;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Ramsey\Uuid\Type\Integer;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationGroup = 'Shop';

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    // value here should be name of the column but for multiple global we need to pass an array
    //that  is why we need override it
    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name','slug','description'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        // we can add multiple records
        return [
            'Brand' => $record->brand->name
        ] ;
    }

    //eager loading for huge amount of records
    public static function  getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['brand']);
    }

    // limiting the number of records
    public static int $globalSearchResultsLimit = 20;

    public static function getNavigationBadge(): ?string
    {
        //in here we can pass some string or any query
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                        ->required()
                        ->live(onBlur: true)
                        ->unique(Product::class,'name',ignoreRecord: true)
                        ->afterStateUpdated(function(string $operation, $state, Forms\Set $set){
                                if ($operation !== 'create' ) {
                                    return;
                                }

                                $set('slug', Str::slug($state));    
                        }),
                        Forms\Components\TextInput::make('slug')
                        ->disabled()
                        ->dehydrated()
                        ->required()
                        ->unique(Product::class,'slug', ignoreRecord: true),
                        Forms\Components\MarkdownEditor::make('description')
                        ->columnSpan('full'),
                    ])->columns(2),

                    Forms\Components\Section::make('Pricing & Inventory')
                    ->schema([
                        Forms\Components\TextInput::make('sku')
                        ->label('SKU (Stock Keeping Value)')
                        ->unique(Product::class,'sku',ignoreRecord: true)
                        ->required(),
                        Forms\Components\TextInput::make('price')
                        ->numeric()
                        ->rules('regex:/^\d{1,6}(\.\d{0,2})?$/')
                        ->required(),
                        Forms\Components\TextInput::make('quantity')
                        // ->rules(['integer','min:0']), u can give the method rules like this
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->required(),
                        Forms\Components\Select::make('type')
                        ->options([
                            'downloadable' => ProductType::DOWNLOADABLE->value,
                            'delivirable'=> ProductType::DELIVIRABLE->value,
                        ])->required()

                    ])->columns(2),

                    
                    ]),

                    Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_visible')
                        ->label('Visibility')
                        ->helperText('Enable or Disable product visibility')
                        ->default(true),
                        Forms\Components\Toggle::make('is_featured')
                        ->label('Featured')
                        ->helperText('Enable or Disable product featured status'),
                        Forms\Components\DatePicker::make('published_at')
                        ->label('Availability')
                        ->default(now()),
                    ]),

                    Forms\Components\Section::make('Image')
                    ->schema([
                        Forms\Components\FileUpload::make('image')
                        ->directory('form-attachments')
                        ->preserveFilenames()
                        ->image()
                        ->imageEditor()
                        ->required()
                    ])->collapsible(),

                    Forms\Components\Section::make('Associations')
                    ->schema([
                        Forms\Components\Select::make('brand_id')
                        ->relationship('brand','name')
                        ->required(),

                        Forms\Components\Select::make('category')
                        ->relationship('category','name')
                        ->multiple()
                        ->required()
                    ]),
                    ]),
                    
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image'),
                Tables\Columns\TextColumn::make('name')
                ->searchable()
                ->sortable(),
                Tables\Columns\TextColumn::make('brand.name')
                ->searchable()
                ->sortable()
                ->toggleable(),
                Tables\Columns\IconColumn::make('is_visible')
                ->sortable()
                ->toggleable()
                ->label('Visibility')
                ->boolean(),
                Tables\Columns\TextColumn::make('price')
                ->sortable()
                ->toggleable(),
                Tables\Columns\TextColumn::make('quantity')
                ->sortable()
                ->toggleable(),
                Tables\Columns\TextColumn::make('published_at')
                ->date()
                ->sortable(),
                Tables\Columns\TextColumn::make('type'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_visible')
                ->label('Visibility')
                ->boolean()
                ->trueLabel('Only Visible Products')
                ->falseLabel('Only Hidden Products')
                ->native(false),

                Tables\Filters\SelectFilter::make('brand')
                ->relationship('brand', 'name')
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }    
}
