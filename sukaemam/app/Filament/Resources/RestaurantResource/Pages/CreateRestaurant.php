<?php

namespace App\Filament\Resources\RestaurantResource\Pages;

use App\Filament\Resources\RestaurantResource;
use App\Models\RestaurantImage;
use Filament\Resources\Pages\CreateRecord;

class CreateRestaurant extends CreateRecord
{
    protected static string $resource = RestaurantResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $imagesData = $this->data['restaurant_images'] ?? [];
        $restaurant = $this->getRecord();

        if (!empty($imagesData)) {
            $images = array_values($imagesData);

            foreach ($images as $index => $imagePath) {
                $restaurant->restaurantImages()->create([
                    'image_url' => $imagePath,
                    'is_main'   => $index === 0,
                ]);
            }
        }
    }
}
