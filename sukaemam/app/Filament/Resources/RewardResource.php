<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RewardResource\Pages;
use App\Models\Reward;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class RewardResource extends Resource
{
    protected static ?string $model = Reward::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';
    
    protected static ?string $navigationGroup = 'Gamification';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Reward Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true),
                        
                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->maxLength(1000)
                            ->rows(3),
                        
                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'voucher' => 'Voucher',
                                'discount' => 'Discount',
                                'free_item' => 'Free Item',
                                'merchandise' => 'Merchandise',
                                'digital' => 'Digital Reward',
                                'experience' => 'Experience',
                            ])
                            ->native(false),
                        
                        Forms\Components\TextInput::make('point_cost')
                            ->label('Point Cost')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10000)
                            ->suffix(' points'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Reward Details')
                    ->schema([
                        Forms\Components\FileUpload::make('image_url')
                            ->label('Reward Image')
                            ->image()
                            ->directory('rewards')
                            ->visibility('public')
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                                '4:3',
                                '1:1',
                            ]),
                        
                        Forms\Components\Select::make('restaurant_id')
                            ->label('Associated Restaurant')
                            ->relationship('restaurant', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Leave empty for general rewards'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Availability Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Only active rewards can be claimed'),
                        
                        Forms\Components\TextInput::make('stock_limit')
                            ->label('Stock Limit')
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->helperText('Leave empty for unlimited stock'),
                        
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expiry Date')
                            ->nullable()
                            ->helperText('Leave empty for no expiry'),
                        
                        Forms\Components\TextInput::make('min_level_required')
                            ->label('Minimum Level Required')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->default(1)
                            ->helperText('Minimum user level to claim this reward'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Image')
                    ->circular()
                    ->size(50),
                
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'voucher' => 'info',
                        'discount' => 'warning',
                        'free_item' => 'success',
                        'merchandise' => 'primary',
                        'digital' => 'secondary',
                        'experience' => 'danger',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('point_cost')
                    ->label('Cost')
                    ->sortable()
                    ->suffix(' pts')
                    ->color('primary'),
                
                Tables\Columns\TextColumn::make('restaurant.name')
                    ->label('Restaurant')
                    ->searchable()
                    ->sortable()
                    ->placeholder('General Reward')
                    ->toggleable(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('stock_remaining')
                    ->label('Stock')
                    ->getStateUsing(function ($record) {
                        if ($record->stock_limit === null) {
                            return '∞';
                        }
                        $claimed = $record->userRewards()->where('is_redeemed', true)->count();
                        return max(0, $record->stock_limit - $claimed);
                    })
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === '∞' => 'success',
                        is_numeric($state) && $state > 10 => 'success',
                        is_numeric($state) && $state > 0 => 'warning',
                        default => 'danger',
                    }),
                
                Tables\Columns\TextColumn::make('claimed_count')
                    ->label('Claimed')
                    ->getStateUsing(fn ($record) => $record->userRewards()->where('is_redeemed', true)->count())
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('No expiry')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'voucher' => 'Voucher',
                        'discount' => 'Discount',
                        'free_item' => 'Free Item',
                        'merchandise' => 'Merchandise',
                        'digital' => 'Digital Reward',
                        'experience' => 'Experience',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All rewards')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
                
                Tables\Filters\SelectFilter::make('restaurant')
                    ->relationship('restaurant', 'name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Expiring Soon')
                    ->query(fn ($query) => 
                        $query->whereNotNull('expires_at')
                              ->where('expires_at', '<=', now()->addDays(7))
                    ),
                
                Tables\Filters\Filter::make('low_stock')
                    ->label('Low Stock')
                    ->query(function ($query) {
                        return $query->whereNotNull('stock_limit')
                                    ->whereRaw('stock_limit - (SELECT COUNT(*) FROM user_rewards WHERE reward_id = rewards.id AND is_redeemed = true) <= 5');
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Reward Details')
                    ->schema([
                        Components\ImageEntry::make('image_url')
                            ->label('Image')
                            ->height(200),
                        Components\TextEntry::make('name')
                            ->size('lg')
                            ->weight('bold'),
                        Components\TextEntry::make('description')
                            ->prose(),
                        Components\TextEntry::make('type')
                            ->badge()
                            ->color('primary'),
                        Components\TextEntry::make('point_cost')
                            ->label('Point Cost')
                            ->suffix(' points')
                            ->color('success'),
                    ])
                    ->columns(2),

                Components\Section::make('Availability')
                    ->schema([
                        Components\TextEntry::make('is_active')
                            ->label('Status')
                            ->badge()
                            ->getStateUsing(fn ($record) => $record->is_active ? 'Active' : 'Inactive')
                            ->color(fn ($record) => $record->is_active ? 'success' : 'danger'),
                        Components\TextEntry::make('stock_limit')
                            ->label('Stock Limit')
                            ->placeholder('Unlimited'),
                        Components\TextEntry::make('expires_at')
                            ->label('Expires At')
                            ->dateTime()
                            ->placeholder('No expiry'),
                        Components\TextEntry::make('min_level_required')
                            ->label('Minimum Level'),
                    ])
                    ->columns(2),

                Components\Section::make('Statistics')
                    ->schema([
                        Components\TextEntry::make('total_claimed')
                            ->label('Total Claimed')
                            ->getStateUsing(fn ($record) => $record->userRewards()->where('is_redeemed', true)->count()),
                        Components\TextEntry::make('pending_claims')
                            ->label('Pending Claims')
                            ->getStateUsing(fn ($record) => $record->userRewards()->where('is_redeemed', false)->count()),
                        Components\TextEntry::make('restaurant.name')
                            ->label('Associated Restaurant')
                            ->placeholder('General Reward'),
                    ])
                    ->columns(3),
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
            'index' => Pages\ListRewards::route('/'),
            'create' => Pages\CreateReward::route('/create'),
            // 'view' => Pages\ViewReward::route('/{record}'),
            'edit' => Pages\EditReward::route('/{record}/edit'),
        ];
    }
}