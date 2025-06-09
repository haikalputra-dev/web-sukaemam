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
        // Use the accessor defined in the model
        $data['restaurant_images'] = $this->record->restaurant_images;
        
        return $data;
    }

    protected function afterSave(): void
    {
        try {
            $restaurant = $this->record;
            
            // Get the new state of images
            $newImagePaths = $this->form->getState()['restaurant_images'] ?? [];
            
            // Get existing image paths from the database
            $restaurant->load('restaurantImages');
            $existingImagePaths = $restaurant->restaurantImages->pluck('image_url')->toArray();

            // Find images to delete (in existing but not in new)
            $imagesToDelete = array_diff($existingImagePaths, $newImagePaths);
            if (!empty($imagesToDelete)) {
                RestaurantImage::where('restaurant_id', $restaurant->id)
                    ->whereIn('image_url', $imagesToDelete)
                    ->delete();
            }

            // Find images to add (in new but not in existing)
            $imagesToAdd = array_diff($newImagePaths, $existingImagePaths);
            if (!empty($imagesToAdd)) {
                foreach ($imagesToAdd as $imagePath) {
                    // Verify the file exists in storage
                    if (!\Storage::disk('public')->exists($imagePath)) {
                        \Log::warning("Image file not found in storage: {$imagePath}");
                        continue;
                    }
                    
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