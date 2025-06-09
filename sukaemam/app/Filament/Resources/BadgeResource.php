<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BadgeResource\Pages;
use App\Models\Badge;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class BadgeResource extends Resource
{
    protected static ?string $model = Badge::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    
    protected static ?string $navigationGroup = 'Gamification';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Badge Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true),
                        
                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->maxLength(1000)
                            ->rows(3),
                        
                        Forms\Components\FileUpload::make('image_url')
                            ->label('Badge Icon')
                            ->image()
                            ->directory('badges')
                            ->visibility('public')
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '1:1',
                            ])
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Achievement Criteria')
                    ->schema([
                        Forms\Components\Select::make('badge_type')
                            ->label('Badge Type')
                            ->required()
                            ->options([
                                'checkin_count' => 'Check-in Count',
                                'restaurant_variety' => 'Restaurant Variety',
                                'consecutive_days' => 'Consecutive Days',
                                'points_milestone' => 'Points Milestone',
                                'special_event' => 'Special Event',
                                'first_time' => 'First Time Achievement',
                                'social' => 'Social Achievement',
                                'explorer' => 'Explorer Achievement',
                            ])
                            ->native(false)
                            ->live(),
                        
                        Forms\Components\TextInput::make('requirement_value')
                            ->label('Requirement Value')
                            ->numeric()
                            ->minValue(1)
                            ->helperText(function (Forms\Get $get) {
                                return match ($get('badge_type')) {
                                    'checkin_count' => 'Number of check-ins required',
                                    'restaurant_variety' => 'Number of different restaurants',
                                    'consecutive_days' => 'Number of consecutive days',
                                    'points_milestone' => 'Total points required',
                                    default => 'Value depends on badge type selected',
                                };
                            }),
                        
                        Forms\Components\Select::make('difficulty_level')
                            ->label('Difficulty Level')
                            ->required()
                            ->options([
                                'bronze' => 'Bronze (Easy)',
                                'silver' => 'Silver (Medium)',
                                'gold' => 'Gold (Hard)',
                                'platinum' => 'Platinum (Very Hard)',
                                'legendary' => 'Legendary (Epic)',
                            ])
                            ->native(false),
                        
                        Forms\Components\TextInput::make('points_reward')
                            ->label('Points Reward')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(1000)
                            ->helperText('Bonus points awarded when badge is earned'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Display Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Only active badges can be earned'),
                        
                        Forms\Components\Toggle::make('is_hidden')
                            ->label('Hidden Badge')
                            ->default(false)
                            ->helperText('Hidden badges are not shown to users until earned'),
                        
                        Forms\Components\TextInput::make('display_order')
                            ->label('Display Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Order in which badges are displayed (lower numbers first)'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Icon')
                    ->circular()
                    ->size(50),
                
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('badge_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'checkin_count' => 'info',
                        'restaurant_variety' => 'success',
                        'consecutive_days' => 'warning',
                        'points_milestone' => 'primary',
                        'special_event' => 'danger',
                        'first_time' => 'secondary',
                        'social' => 'pink',
                        'explorer' => 'emerald',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('difficulty_level')
                    ->label('Difficulty')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'bronze' => 'amber',
                        'silver' => 'gray',
                        'gold' => 'yellow',
                        'platinum' => 'blue',
                        'legendary' => 'purple',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('requirement_value')
                    ->label('Requirement')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('points_reward')
                    ->label('Points')
                    ->suffix(' pts')
                    ->color('success')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('earned_count')
                    ->label('Earned')
                    ->getStateUsing(fn ($record) => $record->userBadges()->count())
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_hidden')
                    ->label('Hidden')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('display_order')
                    ->label('Order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('badge_type')
                    ->label('Badge Type')
                    ->options([
                        'checkin_count' => 'Check-in Count',
                        'restaurant_variety' => 'Restaurant Variety',
                        'consecutive_days' => 'Consecutive Days',
                        'points_milestone' => 'Points Milestone',
                        'special_event' => 'Special Event',
                        'first_time' => 'First Time Achievement',
                        'social' => 'Social Achievement',
                        'explorer' => 'Explorer Achievement',
                    ]),
                
                Tables\Filters\SelectFilter::make('difficulty_level')
                    ->label('Difficulty')
                    ->options([
                        'bronze' => 'Bronze',
                        'silver' => 'Silver',
                        'gold' => 'Gold',
                        'platinum' => 'Platinum',
                        'legendary' => 'Legendary',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All badges')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
                
                Tables\Filters\TernaryFilter::make('is_hidden')
                    ->label('Visibility')
                    ->placeholder('All badges')
                    ->trueLabel('Hidden only')
                    ->falseLabel('Visible only'),
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
            ->defaultSort('display_order', 'asc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Badge Details')
                    ->schema([
                        Components\ImageEntry::make('image_url')
                            ->label('Badge Icon')
                            ->height(150),
                        Components\TextEntry::make('name')
                            ->size('lg')
                            ->weight('bold'),
                        Components\TextEntry::make('description')
                            ->prose(),
                        Components\TextEntry::make('badge_type')
                            ->label('Badge Type')
                            ->badge()
                            ->color('primary'),
                        Components\TextEntry::make('difficulty_level')
                            ->label('Difficulty')
                            ->badge()
                            ->color('warning'),
                    ])
                    ->columns(2),

                Components\Section::make('Requirements')
                    ->schema([
                        Components\TextEntry::make('requirement_value')
                            ->label('Requirement Value'),
                        Components\TextEntry::make('points_reward')
                            ->label('Points Reward')
                            ->suffix(' points')
                            ->color('success'),
                        Components\TextEntry::make('display_order')
                            ->label('Display Order'),
                    ])
                    ->columns(3),

                Components\Section::make('Status & Statistics')
                    ->schema([
                        Components\TextEntry::make('is_active')
                            ->label('Status')
                            ->badge()
                            ->getStateUsing(fn ($record) => $record->is_active ? 'Active' : 'Inactive')
                            ->color(fn ($record) => $record->is_active ? 'success' : 'danger'),
                        Components\TextEntry::make('is_hidden')
                            ->label('Visibility')
                            ->badge()
                            ->getStateUsing(fn ($record) => $record->is_hidden ? 'Hidden' : 'Visible')
                            ->color(fn ($record) => $record->is_hidden ? 'warning' : 'info'),
                        Components\TextEntry::make('earned_count')
                            ->label('Times Earned')
                            ->getStateUsing(fn ($record) => $record->userBadges()->count()),
                    ])
                    ->columns(3),

                Components\Section::make('Recent Earners')
                    ->schema([
                        Components\RepeatableEntry::make('userBadges')
                            ->label('Recent Badge Earners')
                            ->schema([
                                Components\TextEntry::make('user.username')
                                    ->label('User'),
                                Components\TextEntry::make('earned_at')
                                    ->label('Earned At')
                                    ->dateTime(),
                            ])
                            ->columns(2)
                            ->getStateUsing(fn ($record) => 
                                $record->userBadges()->with('user')->latest()->limit(10)->get()
                            ),
                    ]),
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
            'index' => Pages\ListBadges::route('/'),
            'create' => Pages\CreateBadge::route('/create'),
            // 'view' => Pages\ViewBadge::route('/{record}'),
            'edit' => Pages\EditBadge::route('/{record}/edit'),
        ];
    }
}