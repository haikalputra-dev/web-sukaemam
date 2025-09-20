<?php

namespace App\Filament\Resources\RestaurantResource\Pages;

use App\Filament\Resources\RestaurantResource;
use App\Models\RestaurantImage;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRestaurant extends EditRecord
{
    protected static string $resource = RestaurantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Access through the accessor which is already set up to return the correct format
        $data['restaurant_images'] = $this->record->restaurant_images;

        return $data;
    }

    protected function afterSave(): void
    {
        $restaurant = $this->getRecord();
        $newImagePaths = array_values($this->data['restaurant_images'] ?? []);
        $oldImagePaths = $restaurant->restaurantImages()->pluck('image_url')->toArray();

        $imagesToDelete = array_diff($oldImagePaths, $newImagePaths);
        if (!empty($imagesToDelete)) {
            foreach ($imagesToDelete as $path) {
                \Storage::disk('public')->delete($path);
                $restaurant->restaurantImages()->where('image_url', $path)->delete();
            }
        }

        if (!empty($newImagePaths)) {
            foreach ($newImagePaths as $index => $path) {
                $restaurant->restaurantImages()->updateOrCreate(
                    ['image_url' => $path],
                    ['is_main' => $index === 0]
                );
            }
        }
    }
}
