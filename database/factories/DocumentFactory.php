<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Document> */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'type' => 'iapm',
            'category_id' => DocumentCategory::factory(),
            'uploaded_by_role' => Role::ID_ADMIN,
            'uploaded_by_user_id' => User::factory()->asAdmin(),
            'description' => $this->faker->sentence(),
            'file_path' => 'documents/test.pdf',
            'status' => 1,
            'is_pesantren' => true,
            'is_asesor' => false,
        ];
    }
}
