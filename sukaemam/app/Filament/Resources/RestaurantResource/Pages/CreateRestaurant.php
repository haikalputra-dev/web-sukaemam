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
        $restaurant = $this->record;
        $images = $this->form->getState()['restaurant_images'] ?? [];

        if (!empty($images)) {
            foreach ($images as $image) {
                RestaurantImage::create([
                    'restaurant_id' => $restaurant->id,
                    'image_url' => $image,
                ]);
            }
        }
    }
}