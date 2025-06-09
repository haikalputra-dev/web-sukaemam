<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CheckinResource\Pages;
use App\Models\Checkin;
use App\Models\User;
use App\Models\Restaurant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class CheckinResource extends Resource
{
    protected static ?string $model = Checkin::class;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';
    
    protected static ?string $navigationGroup = 'Activity Management';
    
    protected static ?int $navigationSort = 1;

    protected static ?string $label = 'Check-in';
    
    protected static ?string $pluralLabel = 'Check-ins';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Check-in Details')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->relationship('user', 'username')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('username')
                                    ->required(),
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required(),
                            ]),
                        
                        Forms\Components\Select::make('restaurant_id')
                            ->label('Restaurant')
                            ->relationship('restaurant', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required(),
                                Forms\Components\TextInput::make('address')
                                    ->required(),
                            ]),
                        
                        Forms\Components\DateTimePicker::make('checkin_time')
                            ->label('Check-in Time')
                            ->required()
                            ->default(now()),
                        
                        Forms\Components\TextInput::make('points_earned')
                            ->label('Points Earned')
                            ->numeric()
                            ->default(10)
                            ->minValue(0)
                            ->maxValue(1000)
                            ->helperText('Points awarded for this check-in'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\TextInput::make('qr_code_data')
                            ->label('QR Code Data')
                            ->maxLength(500)
                            ->helperText('Data scanned from QR code')
                            ->columnSpanFull(),
                        
                        Forms\Components\FileUpload::make('photo_url')
                            ->label('Photo')
                            ->image()
                            ->directory('checkin-photos')
                            ->visibility('public')
                            ->helperText('Optional photo taken during check-in'),
                    ]),
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
                
                Tables\Columns\TextColumn::make('restaurant.name')
                    ->label('Restaurant')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('points_earned')
                    ->label('Points')
                    ->badge()
                    ->color('success')
                    ->sortable(),
                
                Tables\Columns\ImageColumn::make('photo_url')
                    ->label('Photo')
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl(null),
                
                Tables\Columns\TextColumn::make('checkin_time')
                    ->label('Check-in Time')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn($record) => $record->checkin_time->format('M j, Y')),
                
                Tables\Columns\TextColumn::make('restaurant.address')
                    ->label('Location')
                    ->limit(30)
                    ->tooltip(fn($record) => $record->restaurant->address)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'username')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\SelectFilter::make('restaurant')
                    ->relationship('restaurant', 'name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('checkin_time', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('checkin_time', '<=', $data['until']));
                    }),
                
                Tables\Filters\Filter::make('with_photo')
                    ->label('With Photo')
                    ->query(fn ($query) => $query->whereNotNull('photo_url')),
                
                Tables\Filters\SelectFilter::make('points_range')
                    ->label('Points Range')
                    ->options([
                        '1-10' => '1-10 points',
                        '11-25' => '11-25 points',
                        '26-50' => '26-50 points',
                        '51+' => '51+ points',
                    ])
                    ->query(function ($query, array $data) {
                        if (!$data['value']) return $query;
                        
                        return match($data['value']) {
                            '1-10' => $query->whereBetween('points_earned', [1, 10]),
                            '11-25' => $query->whereBetween('points_earned', [11, 25]),
                            '26-50' => $query->whereBetween('points_earned', [26, 50]),
                            '51+' => $query->where('points_earned', '>=', 51),
                        };
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
            ->defaultSort('checkin_time', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Check-in Information')
                    ->schema([
                        Components\TextEntry::make('user.username')
                            ->label('User'),
                        Components\TextEntry::make('restaurant.name')
                            ->label('Restaurant'),
                        Components\TextEntry::make('checkin_time')
                            ->label('Check-in Time')
                            ->dateTime(),
                        Components\TextEntry::make('points_earned')
                            ->label('Points Earned')
                            ->badge()
                            ->color('success'),
                    ])
                    ->columns(2),

                Components\Section::make('Location Details')
                    ->schema([
                        Components\TextEntry::make('restaurant.address')
                            ->label('Restaurant Address'),
                        Components\TextEntry::make('restaurant.description')
                            ->label('Restaurant Description')
                            ->limit(100),
                    ]),

                Components\Section::make('Technical Details')
                    ->schema([
                        Components\TextEntry::make('qr_code_data')
                            ->label('QR Code Data')
                            ->placeholder('No QR data recorded'),
                        Components\ImageEntry::make('photo_url')
                            ->label('Check-in Photo')
                            ->height(200),
                    ])
                    ->columns(2),
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
            'index' => Pages\ListCheckins::route('/'),
            'create' => Pages\CreateCheckin::route('/create'),
            // 'view' => Pages\ViewCheckin::route('/{record}'),
            'edit' => Pages\EditCheckin::route('/{record}/edit'),
        ];
    }
}