<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationGroup = 'User Management';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->schema([
                        Forms\Components\TextInput::make('username')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        
                        Forms\Components\FileUpload::make('avatar_url')
                            ->label('Avatar')
                            ->image()
                            ->directory('avatars')
                            ->visibility('public'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Gamification')
                    ->schema([
                        Forms\Components\TextInput::make('level')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(100),
                        
                        Forms\Components\TextInput::make('total_points')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Timestamps')
                    ->schema([
                        Forms\Components\DateTimePicker::make('created_at')
                            ->disabled(),
                        
                        Forms\Components\DateTimePicker::make('updated_at')
                            ->disabled(),
                    ])
                    ->columns(2)
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('Avatar')
                    ->circular()
                    ->defaultImageUrl(fn() => 'https://ui-avatars.com/api/?name=User&background=0D8ABC&color=fff'),
                
                Tables\Columns\TextColumn::make('username')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('level')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 50 => 'success',
                        $state >= 25 => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('total_points')
                    ->label('Points')
                    ->numeric()
                    ->sortable()
                    ->color('primary'),
                
                Tables\Columns\TextColumn::make('checkins_count')
                    ->label('Check-ins')
                    ->counts('checkins')
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('level')
                    ->options([
                        '1-10' => 'Beginner (1-10)',
                        '11-25' => 'Intermediate (11-25)',
                        '26-50' => 'Advanced (26-50)',
                        '51-100' => 'Expert (51-100)',
                    ])
                    ->query(function ($query, array $data) {
                        if (!$data['value']) return $query;
                        
                        [$min, $max] = explode('-', $data['value']);
                        return $query->whereBetween('level', [(int)$min, (int)$max]);
                    }),
                
                Tables\Filters\Filter::make('active_users')
                    ->label('Active This Month')
                    ->query(fn ($query) => 
                        $query->whereHas('checkins', fn ($q) => 
                            $q->where('checkin_time', '>=', now()->startOfMonth())
                        )
                    ),
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
                Components\Section::make('User Details')
                    ->schema([
                        Components\ImageEntry::make('avatar_url')
                            ->label('Avatar')
                            ->circular(),
                        Components\TextEntry::make('username'),
                        Components\TextEntry::make('email'),
                        Components\TextEntry::make('level')
                            ->badge()
                            ->color('primary'),
                        Components\TextEntry::make('total_points')
                            ->label('Total Points')
                            ->color('success'),
                    ])
                    ->columns(2),

                Components\Section::make('Activity Stats')
                    ->schema([
                        Components\TextEntry::make('checkins_count')
                            ->label('Total Check-ins')
                            ->getStateUsing(fn ($record) => $record->checkins()->count()),
                        Components\TextEntry::make('badges_count')
                            ->label('Badges Earned')
                            ->getStateUsing(fn ($record) => $record->userBadges()->count()),
                        Components\TextEntry::make('rewards_claimed')
                            ->label('Rewards Claimed')
                            ->getStateUsing(fn ($record) => $record->userRewards()->where('is_redeemed', true)->count()),
                    ])
                    ->columns(3),

                Components\Section::make('Recent Activity')
                    ->schema([
                        Components\RepeatableEntry::make('checkins')
                            ->label('Recent Check-ins')
                            ->schema([
                                Components\TextEntry::make('restaurant.name')
                                    ->label('Restaurant'),
                                Components\TextEntry::make('points_earned')
                                    ->label('Points'),
                                Components\TextEntry::make('checkin_time')
                                    ->label('Date')
                                    ->dateTime(),
                            ])
                            ->columns(3)
                            ->getStateUsing(fn ($record) => 
                                $record->checkins()->with('restaurant')->latest()->limit(5)->get()
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            // 'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}