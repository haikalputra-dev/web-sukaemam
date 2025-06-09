<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserBadgeResource\Pages;
use App\Models\UserBadge;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class UserBadgeResource extends Resource
{
    protected static ?string $model = UserBadge::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';
    
    protected static ?string $navigationGroup = 'Activity Management';
    
    protected static ?int $navigationSort = 3;

    protected static ?string $label = 'User Badge';
    
    protected static ?string $pluralLabel = 'User Badges';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Badge Achievement')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->relationship('user', 'username')
                            ->searchable()
                            ->preload()
                            ->required(),
                        
                        Forms\Components\Select::make('badge_id')
                            ->label('Badge')
                            ->relationship('badge', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        
                        Forms\Components\DateTimePicker::make('earned_at')
                            ->label('Earned At')
                            ->required()
                            ->default(now()),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('user.avatar_url')
                    ->label('User')
                    ->circular()
                    ->defaultImageUrl(fn($record) => 
                        'https://ui-avatars.com/api/?name=' . urlencode($record->user->username ?? 'User') . '&background=0D8ABC&color=fff'
                    ),
                
                Tables\Columns\TextColumn::make('user.username')
                    ->label('Username')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\ImageColumn::make('badge.image_url')
                    ->label('Badge')
                    ->circular()
                    ->size(50),
                
                Tables\Columns\TextColumn::make('badge.name')
                    ->label('Badge Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('badge.difficulty_level')
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
                
                Tables\Columns\TextColumn::make('badge.badge_type')
                    ->label('Type')
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('badge.points_reward')
                    ->label('Points')
                    ->suffix(' pts')
                    ->color('success'),
                
                Tables\Columns\TextColumn::make('earned_at')
                    ->label('Earned')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn($record) => $record->earned_at->format('M j, Y')),
                
                Tables\Columns\TextColumn::make('user.level')
                    ->label('User Level')
                    ->badge()
                    ->color('primary')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'username')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\SelectFilter::make('badge')
                    ->relationship('badge', 'name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\SelectFilter::make('difficulty_level')
                    ->label('Badge Difficulty')
                    ->options([
                        'bronze' => 'Bronze',
                        'silver' => 'Silver',
                        'gold' => 'Gold',
                        'platinum' => 'Platinum',
                        'legendary' => 'Legendary',
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['value']) {
                            $query->whereHas('badge', fn ($q) => 
                                $q->where('difficulty_level', $data['value'])
                            );
                        }
                    }),
                
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
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['value']) {
                            $query->whereHas('badge', fn ($q) => 
                                $q->where('badge_type', $data['value'])
                            );
                        }
                    }),
                
                Tables\Filters\Filter::make('recent_achievements')
                    ->label('Recent Achievements (7 days)')
                    ->query(fn ($query) => 
                        $