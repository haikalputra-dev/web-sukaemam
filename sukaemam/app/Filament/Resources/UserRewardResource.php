<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserRewardResource\Pages;
use App\Models\UserReward;
use App\Models\User;
use App\Models\Reward;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserRewardResource extends Resource
{
    protected static ?string $model = UserReward::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Gamification';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'User Reward';

    protected static ?string $pluralModelLabel = 'User Rewards';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('User')
                    ->options(User::all()->pluck('username', 'id'))
                    ->required()
                    ->searchable(),
                
                Forms\Components\Select::make('reward_id')
                    ->label('Reward')
                    ->options(Reward::where('is_active', true)->pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                
                Forms\Components\TextInput::make('qr_code_data')
                    ->label('QR Code Data')
                    ->helperText('Unique code for reward redemption')
                    ->maxLength(255),
                
                Forms\Components\DateTimePicker::make('claimed_at')
                    ->label('Claimed At')
                    ->default(now())
                    ->required(),
                
                Forms\Components\Toggle::make('is_redeemed')
                    ->label('Is Redeemed')
                    ->default(false),
                
                Forms\Components\DateTimePicker::make('redeemed_at')
                    ->label('Redeemed At')
                    ->hidden(fn (Forms\Get $get): bool => !$get('is_redeemed')),
                
                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('Expires At')
                    ->helperText('Leave empty for no expiration')
                    ->after('claimed_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.username')
                    ->label('User')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('reward.name')
                    ->label('Reward')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('reward.point_cost')
                    ->label('Point Cost')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_redeemed')
                    ->label('Redeemed')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('claimed_at')
                    ->label('Claimed At')
                    ->dateTime()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('redeemed_at')
                    ->label('Redeemed At')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not redeemed'),
                
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires At')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('No expiration')
                    ->color(fn ($record) => $record->expires_at && $record->expires_at->isPast() && !$record->is_redeemed ? 'danger' : null),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('User')
                    ->options(User::all()->pluck('username', 'id'))
                    ->searchable(),
                
                Tables\Filters\SelectFilter::make('reward_id')
                    ->label('Reward')
                    ->options(Reward::all()->pluck('name', 'id'))
                    ->searchable(),
                
                Tables\Filters\TernaryFilter::make('is_redeemed')
                    ->label('Redemption Status')
                    ->placeholder('All')
                    ->trueLabel('Redeemed')
                    ->falseLabel('Not Redeemed'),
                
                Tables\Filters\Filter::make('expired')
                    ->label('Expired')
                    ->query(fn (Builder $query): Builder => $query->where('expires_at', '<', now())->where('is_redeemed', false))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_redeemed')
                    ->label('Mark as Redeemed')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (UserReward $record) {
                        $record->update([
                            'is_redeemed' => true,
                            'redeemed_at' => now(),
                        ]);
                    })
                    ->visible(fn (UserReward $record): bool => !$record->is_redeemed),
                
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('mark_redeemed')
                        ->label('Mark as Redeemed')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update([
                                    'is_redeemed' => true,
                                    'redeemed_at' => now(),
                                ]);
                            });
                        }),
                ]),
            ])
            ->defaultSort('claimed_at', 'desc');
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
            'index' => Pages\ListUserRewards::route('/'),
            'create' => Pages\CreateUserReward::route('/create'),
            'edit' => Pages\EditUserReward::route('/{record}/edit'),
        ];
    }
}