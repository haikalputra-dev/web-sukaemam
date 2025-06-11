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
        try {
            $restaurant = $this->record;
            
            // Get the new state of images
            $newImagePaths = $this->form->getState()['restaurant_images'] ?? [];
            
            // Delete all existing images that are not in the new set
            RestaurantImage::where('restaurant_id', $restaurant->id)
                ->whereNotIn('image_url', $newImagePaths)
                ->get()
                ->each(function ($image) {
                    // Delete the file from storage
                    if (\Storage::disk('public')->exists($image->image_url)) {
                        \Storage::disk('public')->delete($image->image_url);
                    }
                    // Delete the database record
                    $image->delete();
                });

            // Get current image paths after deletion
            $existingImagePaths = $restaurant->restaurantImages()->pluck('image_url')->toArray();

            // Add only new images that don't exist yet
            $imagesToAdd = array_diff($newImagePaths, $existingImagePaths);
            foreach ($imagesToAdd as $imagePath) {
                if (\Storage::disk('public')->exists($imagePath)) {
                    RestaurantImage::create([
                        'restaurant_id' => $restaurant->id,
                        'image_url' => $imagePath,
                        'uploaded_at' => now(),
                    ]);
                }
            }
            
            // Force reload the relationship
            $restaurant->load('restaurantImages');
            
        } catch (\Exception $e) {
            \Log::error("Error saving restaurant images: " . $e->getMessage());
            throw $e;
        }
    }
}