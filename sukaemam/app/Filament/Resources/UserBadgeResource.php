<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserBadgeResource\Pages;
use App\Models\UserBadge;
use App\Models\User;
use App\Models\Badge;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserBadgeResource extends Resource
{
    protected static ?string $model = UserBadge::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationGroup = 'Gamification';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'User Badge';

    protected static ?string $pluralModelLabel = 'User Badges';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('User')
                    ->options(User::all()->pluck('username', 'id'))
                    ->required()
                    ->searchable(),
                
                Forms\Components\Select::make('badge_id')
                    ->label('Badge')
                    ->options(Badge::all()->pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                
                Forms\Components\DateTimePicker::make('earned_at')
                    ->label('Earned At')
                    ->default(now())
                    ->required(),
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
                
                Tables\Columns\ImageColumn::make('badge.image_url')
                    ->label('Badge')
                    ->size(40)
                    ->circular(),
                
                Tables\Columns\TextColumn::make('badge.name')
                    ->label('Badge Name')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('earned_at')
                    ->label('Earned At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('User')
                    ->options(User::all()->pluck('username', 'id'))
                    ->searchable(),
                
                Tables\Filters\SelectFilter::make('badge_id')
                    ->label('Badge')
                    ->options(Badge::all()->pluck('name', 'id'))
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('earned_at', 'desc');
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
            'index' => Pages\ListUserBadges::route('/'),
            'create' => Pages\CreateUserBadge::route('/create'),
            'edit' => Pages\EditUserBadge::route('/{record}/edit'),
        ];
    }
}