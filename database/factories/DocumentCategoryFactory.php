<?php

namespace Database\Factories;

use App\Models\DocumentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<DocumentCategory> */
class DocumentCategoryFactory extends Factory
{
    protected $model = DocumentCategory::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(),
            'icon' => 'document',
            'visibility' => DocumentCategory::VISIBILITY_PUBLIC,
            'pesantren_can_upload' => false,
            'asesor_can_upload' => false,
            'is_active' => true,
            'sort_order' => 1,
        ];
    }
}
