<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\DocumentCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the default document categories that match the legacy ENUM
 * (`iapm`, `kartu_kendali`, `visitasi`) plus correct visibility.
 *
 * Idempotent: re-running this seeder does NOT duplicate categories
 * thanks to updateOrCreate on the unique slug column. Existing
 * documents (if any) are linked back to their category by slug.
 */
class DocumentCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'slug' => 'iapm',
                'name' => 'IAPM (Instrumen Akreditasi Penjaminan Mutu)',
                'description' => 'Panduan dan instrumen pengisian akreditasi pesantren Muhammadiyah. Dapat diakses oleh semua role.',
                'icon' => 'document-stack',
                'visibility' => DocumentCategory::VISIBILITY_PUBLIC,
                'pesantren_can_upload' => false,
                'asesor_can_upload' => false,
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'slug' => 'kartu_kendali',
                'name' => 'Kartu Kendali Visitasi',
                'description' => 'Lembar penilaian kinerja asesor dari sudut pandang pesantren. Hanya admin dan pesantren yang dapat melihat template ini.',
                'icon' => 'clipboard-check',
                'visibility' => DocumentCategory::VISIBILITY_PESANTREN_SECRET,
                'pesantren_can_upload' => true,
                'asesor_can_upload' => false,
                'is_active' => true,
                'sort_order' => 20,
            ],
            [
                'slug' => 'visitasi',
                'name' => 'Laporan Visitasi',
                'description' => 'Template dan panduan laporan visitasi asesor. Hanya admin dan asesor yang dapat melihat template ini.',
                'icon' => 'document-up',
                'visibility' => DocumentCategory::VISIBILITY_ASESOR_SECRET,
                'pesantren_can_upload' => false,
                'asesor_can_upload' => true,
                'is_active' => true,
                'sort_order' => 30,
            ],
        ];

        foreach ($categories as $row) {
            DocumentCategory::updateOrCreate(
                ['slug' => $row['slug']],
                $row,
            );
        }

        // Link any pre-existing documents to their category via the legacy `type` slug.
        DB::table('documents')
            ->whereNull('category_id')
            ->whereNotNull('type')
            ->orderBy('id')
            ->get(['id', 'type'])
            ->each(function ($doc) {
                $category = DocumentCategory::where('slug', $doc->type)->first();
                if ($category) {
                    DB::table('documents')
                        ->where('id', $doc->id)
                        ->update(['category_id' => $category->id]);
                }
            });
    }
}
