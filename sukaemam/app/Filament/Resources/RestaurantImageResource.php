<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RestaurantImageResource\Pages;
use App\Models\RestaurantImage;
use App\Models\Restaurant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RestaurantImageResource extends Resource
{
    protected static ?string $model = RestaurantImage::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Restaurant Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('restaurant_id')
                    ->label('Restaurant')
                    ->options(Restaurant::all()->pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                
                Forms\Components\FileUpload::make('image_url')
                    ->label('Restaurant Image')
                    ->image()
                    ->directory('restaurant-images')
                    ->required()
                    ->maxSize(2048)
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
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->imageEditor()
                    ->imageEditorAspectRatios([
                        '16:9',
                        '4:3',
                        '1:1',
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('restaurant.name')
                    ->label('Restaurant')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Image')
                    ->size(80)
                    ->circular(false),
                
                Tables\Columns\TextColumn::make('uploaded_at')
                    ->label('Uploaded At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('restaurant_id')
                    ->label('Restaurant')
                    ->options(Restaurant::all()->pluck('name', 'id'))
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
            ->defaultSort('uploaded_at', 'desc');
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
            'index' => Pages\ListRestaurantImages::route('/'),
            'create' => Pages\CreateRestaurantImage::route('/create'),
            'edit' => Pages\EditRestaurantImage::route('/{record}/edit'),
        ];
    }
}