<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RestaurantResource\Pages;
use App\Models\Restaurant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Support\Facades\Storage;

class RestaurantResource extends Resource
{
    protected static ?string $model = Restaurant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Restaurants';

    protected static ?string $navigationGroup = 'Content Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Restaurant Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),

                        TextInput::make('address')
                            ->required()
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Location')
                    ->schema([
                        TextInput::make('latitude')
                            ->required()
                            ->numeric()
                            ->step('any')
                            ->placeholder('-6.928034'),

                        TextInput::make('longitude')
                            ->required()
                            ->numeric()
                            ->step('any')
                            ->placeholder('106.628167'),
                    ])->columns(2),

                Section::make('Images')
                    ->schema([
                        FileUpload::make('restaurant_images')
                            ->label('Restaurant Images')
                            ->image()
                            ->imageEditor()
                            ->multiple()
                            ->directory('restaurant-images')
                            ->disk('public')
                            ->visibility('public')
                            ->getUploadedFileNameForStorageUsing(
                                function ($file) {
                                    $timestamp = now()->timestamp;
                                    $random = str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);
                                    $extension = $file->getClientOriginalExtension();
                                    return "restaurant-photos-{$timestamp}-{$random}.{$extension}";
                                }
                            )
                            ->maxFiles(10)
                            ->reorderable()
                            ->columnSpanFull()
                            ->helperText('Upload multiple images for this restaurant. Max 10 images.')
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->imagePreviewHeight('250')
                            ->loadingIndicatorPosition('center')
                            ->panelLayout('grid')
                            ->removeUploadedFileButtonPosition('center')
                            ->uploadProgressIndicatorPosition('center'),
                    ])->columns(1),

                Section::make('Rating')
                    ->schema([
                        TextInput::make('average_rating')
                            ->label('Average Rating')
                            ->numeric()
                            ->step(0.1)
                            ->minValue(0)
                            ->maxValue(5)
                            ->default(0)
                            ->suffix('/ 5')
                            ->helperText('This will be auto-calculated from reviews'),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('restaurantImages.image_url')
                    ->label('Images')
                    ->circular()
                    ->stacked()
                    ->limit(3)
                    ->limitedRemainingText()
                    ->size(80),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('address')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),

                TextColumn::make('average_rating')
                    ->label('Rating')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        $state >= 4.5 => 'success',
                        $state >= 4.0 => 'warning',
                        $state >= 3.0 => 'gray',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => number_format($state, 1) . ' ⭐'),

                TextColumn::make('latitude')
                    ->label('Lat')
                    ->formatStateUsing(fn (string $state): string => number_format($state, 6))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('longitude')
                    ->label('Lng')
                    ->formatStateUsing(fn (string $state): string => number_format($state, 6))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('high_rated')
                    ->label('High Rated (4.0+)')
                    ->query(fn ($query) => $query->where('average_rating', '>=', 4.0)),

                Tables\Filters\Filter::make('recent')
                    ->label('Added This Month')
                    ->query(fn ($query) => $query->whereMonth('created_at', now()->month)),
            ])
            ->actions([
                EditAction::make(),
                Action::make('generate_qr')
                    ->label('Generate QR')
                    ->icon('heroicon-o-qr-code')
                    ->color('success')
                    ->action(function (Restaurant $record) {
                        \Filament\Notifications\Notification::make()
                            ->title('QR Code Generated')
                            ->body("QR code untuk restoran {$record->name} berhasil di-generate!")
                            ->success()
                            ->send();
                    })
                    ->url(fn(Restaurant $record) => url("/generate-qr/{$record->id}?dl=1"))
                    ->openUrlInNewTab(),
            ])

            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            // TODO: Add relations (Reviews, CheckIns, etc.)
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRestaurants::route('/'),
            'create' => Pages\CreateRestaurant::route('/create'),
            'edit' => Pages\EditRestaurant::route('/{record}/edit'),
        ];
    }
}
