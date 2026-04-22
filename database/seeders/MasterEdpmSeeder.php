<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterEdpmKomponen;
use App\Models\MasterEdpmButir;
use Illuminate\Support\Facades\DB;

class MasterEdpmSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('master_edpm_butirs')->delete();
        DB::table('master_edpm_komponens')->delete();

        $komponens = [
            [
                'nama' => 'MUTU LULUSAN',
                'butirs' => [
                    ['no_sk' => '1', 'nomor_butir' => '1', 'butir_pernyataan' => 'Menjadi pribadi yang bertaqwa (berakidah lurus; beribadah secara benar; dan berakhlak mulia)'],
                    ['no_sk' => '2', 'nomor_butir' => '2', 'butir_pernyataan' => 'Santri Mampu Membaca, Menghafal, dan Memahami Makna Al-Quran.'],
                    ['no_sk' => '3', 'nomor_butir' => '3', 'butir_pernyataan' => 'Santri Mampu Menjadi Pendidik, Muballigh, dan Imam Shalat.'],
                    ['no_sk' => '3', 'nomor_butir' => '4', 'butir_pernyataan' => 'Santri Memiliki Kompetensi Kepemimpinan, Kekaderan dan Keorganisasian IPM, HW, dan Tapak Suci'],
                    ['no_sk' => '2', 'nomor_butir' => '5', 'butir_pernyataan' => 'Mahir Berbahasa Arab dan Inggris'],
                    ['no_sk' => '4', 'nomor_butir' => '6', 'butir_pernyataan' => 'Santri berjiwa mandiri dan wirausaha.'],
                    ['no_sk' => '3', 'nomor_butir' => '7', 'butir_pernyataan' => 'Memiliki Keterampilan Sosial dan Public Speaking'],
                    ['no_sk' => '2', 'nomor_butir' => '8', 'butir_pernyataan' => 'Memiliki Keterampilan Berkemajuan (Menguasai Kutub Turats, IPTEK, TIK, dan Jejaring)'],
                ]
            ],
            [
                'nama' => 'PROSES PEMBELAJARAN',
                'butirs' => [
                    ['no_sk' => '1', 'nomor_butir' => '9', 'butir_pernyataan' => 'Proses Pembelajaran dilaksanakan secara holistik, integratif, dan HOTS'],
                    ['no_sk' => '1', 'nomor_butir' => '10', 'butir_pernyataan' => 'Pembelajaran yang Menerapkan Nilai-Nilai Keteladanan, Menumbuhkan Kemauan, dan Mengembangkan Kreativitas'],
                    ['no_sk' => '1', 'nomor_butir' => '11', 'butir_pernyataan' => 'Pemanfaatan TIK untuk Pembelajaran yang Efektif dan Efisien'],
                    ['no_sk' => '1', 'nomor_butir' => '12', 'butir_pernyataan' => 'Proses Pembelajaran Menggunakan Strategi, Model, dan Metode yang Aktif, Inovatif, Efektif, Menyenangkan, dan Menantang'],
                    ['no_sk' => '1', 'nomor_butir' => '13', 'butir_pernyataan' => 'Melakukan Pengayaan Kutub Turats dalam Proses Pembelajaran'],
                    ['no_sk' => '2', 'nomor_butir' => '14', 'butir_pernyataan' => 'Melakukan Penilaian Proses dan Hasil Sebagai Dasar Perbaikan yang Dilaksanakan Secara Sistematis'],
                    ['no_sk' => '3', 'nomor_butir' => '15', 'butir_pernyataan' => 'Santri sebagai Pembelajar Sepanjang Hayat'],
                    ['no_sk' => '3', 'nomor_butir' => '16', 'butir_pernyataan' => 'Santri Menunjukkan Sikap Tawadhu\' dan Ihtiram Kepada Ustadz'],
                    ['no_sk' => '2', 'nomor_butir' => '17', 'butir_pernyataan' => 'Peningkatan Soft Skills dan Hard Skills secara Seimbang.'],
                    ['no_sk' => '1', 'nomor_butir' => '18', 'butir_pernyataan' => 'Menggunakan Bahasa Asing Sebagai Bahasa Pengantar'],
                ]
            ],
            [
                'nama' => 'MUTU USTAZ',
                'butirs' => [
                    ['no_sk' => '1', 'nomor_butir' => '19', 'butir_pernyataan' => 'Ustadz Melakukan Evaluasi Diri, Refleksi dan Perbaikan Kinerja Secara Berkala dan Terukur'],
                    ['no_sk' => '1', 'nomor_butir' => '20', 'butir_pernyataan' => 'Pengembangan Kompetensi Ustadz Secara Berkelanjutan'],
                    ['no_sk' => '2', 'nomor_butir' => '21', 'butir_pernyataan' => 'Ustadz yang Mengampu Dirasah Islamiyah Memiliki Kemampuan Berbahasa Arab Secara Aktif'],
                    ['no_sk' => '2', 'nomor_butir' => '22', 'butir_pernyataan' => 'Ustadz Mampu Menggunakan Pengantar Bahasa Arab/Inggris dalam Pembukaan dan Penutupan Pembelajaran.'],
                    ['no_sk' => '2', 'nomor_butir' => '23', 'butir_pernyataan' => 'Ustadz Memiliki Pemahaman Tentang Karakteristik Warga Muhammadiyah dan Mampu Mengamalkannya.'],
                    ['no_sk' => '3', 'nomor_butir' => '24', 'butir_pernyataan' => 'Aktif dalam Kegiatan Persyarikatan Muhammadiyah'],
                    ['no_sk' => '3', 'nomor_butir' => '25', 'butir_pernyataan' => 'Ustadz Melakukan Pengembangan dan Pembiasaan Hidup Islami.'],
                    ['no_sk' => '3', 'nomor_butir' => '26', 'butir_pernyataan' => 'Ustadz Menjadi Uswatun Hasanah'],
                    ['no_sk' => '1', 'nomor_butir' => '27', 'butir_pernyataan' => 'Menyusun Perencanaan dengan Mengembangkan Strategi, Model, Metode, Teknik, dan Media Pembelajaran Yang Aktif, Kreatif, dan Inovatif'],
                    ['no_sk' => '1', 'nomor_butir' => '28', 'butir_pernyataan' => 'Memiliki kemampuan Informasi dan Teknologi (IT)'],
                ]
            ],
            [
                'nama' => 'MANAJEMEN PESANTREN',
                'butirs' => [
                    ['no_sk' => '1', 'nomor_butir' => '29', 'butir_pernyataan' => 'Pesantren Merumuskan Visi, Misi dan Tujuan Serta Mengimplementasikannya'],
                    ['no_sk' => '1', 'nomor_butir' => '30', 'butir_pernyataan' => 'Pesantren Membuat Rencana Kerja Jangka Menengah (RKJM), Rencana Kerja Tahunan (RKT), dan Rencana Kerja Anggaran Pesantren (RKAP).'],
                    ['no_sk' => '1', 'nomor_butir' => '31', 'butir_pernyataan' => 'Mudir Mampu Merencanakan, Melaksanakan, Mengevaluasi, Melakukan Tindak Lanjut Atas Sistem Operasional Prosedur (SOP), Peraturan Akademik, dan Sistem Informasi Manajemen (SIM)'],
                    ['no_sk' => '3', 'nomor_butir' => '32', 'butir_pernyataan' => 'Pesantren Mengelola Sarana dan Prasarana'],
                    ['no_sk' => '3', 'nomor_butir' => '33', 'butir_pernyataan' => 'Pesantren Mengelola Unit Usaha'],
                    ['no_sk' => '2', 'nomor_butir' => '34', 'butir_pernyataan' => 'Kepemimpinan Yang Kreatif, Inovatif, Partisipatif, Kolaboratif, Transformatif dan Efektif'],
                    ['no_sk' => '4', 'nomor_butir' => '35', 'butir_pernyataan' => 'Pesantren melibatkan masyarakat dalam pelaksanaan program.'],
                    ['no_sk' => '2', 'nomor_butir' => '36', 'butir_pernyataan' => 'Menciptakan Budaya Ta\'dzim dan Ta\'awun di Pesantren'],
                    ['no_sk' => '2', 'nomor_butir' => '37', 'butir_pernyataan' => 'Menciptakan Lingkungan Berbahasa Arab dan Inggris di Pesantren.'],
                    ['no_sk' => '4', 'nomor_butir' => '38', 'butir_pernyataan' => 'Pesantren Melakukan Pembinaan dan Pengelolaan alumni'],
                    ['no_sk' => '3', 'nomor_butir' => '39', 'butir_pernyataan' => 'Pesantren Menyelenggarakan Pembinaan Kegiatan Kesantrian Untuk Mengembangkan Minat dan Bakat Santri.'],
                    ['no_sk' => '4', 'nomor_butir' => '40', 'butir_pernyataan' => 'Pesantren melaksanakan Penjaminan Mutu Internal'],
                ]
            ],
            [
                'nama' => 'B. INDIKATOR PEMENUHAN RELATIF',
                'butirs' => [
                    ['no_sk' => '', 'nomor_butir' => '1', 'butir_pernyataan' => 'Kualifikasi akademik guru minimum sarjana (S1) atau diploma empat'],
                    ['no_sk' => '', 'nomor_butir' => '2', 'butir_pernyataan' => 'Ustadz pesantren memiliki ijazah atau alumni pesantren'],
                    ['no_sk' => '', 'nomor_butir' => '3', 'butir_pernyataan' => 'Ustadz yang mengajar sesuai latar belakang pendidikan Dirasah Islamiyah'],
                    ['no_sk' => '', 'nomor_butir' => '4', 'butir_pernyataan' => 'Ustadz yang mengajar memiliki kompetensi Bahasa Arab'],
                    ['no_sk' => '', 'nomor_butir' => '5', 'butir_pernyataan' => 'Ustadz yang mengajar memiliki NBM'],
                    ['no_sk' => '', 'nomor_butir' => '6', 'butir_pernyataan' => 'Ustadz yang mengajar memiliki sertifikat perkaderan Muhammadiyah'],
                    ['no_sk' => '', 'nomor_butir' => '7', 'butir_pernyataan' => 'Ustadz yang mengajar aktif di persyarikatan'],
                    ['no_sk' => '', 'nomor_butir' => '8', 'butir_pernyataan' => 'Pesantren memiliki perpustakaan kitab turats dan kontemporer'],
                    ['no_sk' => '', 'nomor_butir' => '9', 'butir_pernyataan' => 'Jumlah rombongan belajar'],
                    ['no_sk' => '', 'nomor_butir' => '10', 'butir_pernyataan' => 'Pesantren memiliki asrama dengan daya yang mencukupi kebutuhan'],
                    ['no_sk' => '', 'nomor_butir' => '11', 'butir_pernyataan' => 'Pesantren memiliki lapangan'],
                    ['no_sk' => '', 'nomor_butir' => '12', 'butir_pernyataan' => 'Pesantren memiliki masjid daya yang mencukupi kebutuhan'],
                    ['no_sk' => '', 'nomor_butir' => '13', 'butir_pernyataan' => 'Pesantren memiliki ruang belajar daya yang mencukupi kebutuhan'],
                    ['no_sk' => '', 'nomor_butir' => '14', 'butir_pernyataan' => 'Pesantren memiliki dapur umum yang mencukupi kebutuhan'],
                    ['no_sk' => '', 'nomor_butir' => '15', 'butir_pernyataan' => 'Pesantren memiliki kamar MCK daya yang mencukupi kebutuhan'],
                    ['no_sk' => '', 'nomor_butir' => '16', 'butir_pernyataan' => 'Pesantren memiliki ruang kantor yang mencukupi kebutuhan'],
                    ['no_sk' => '', 'nomor_butir' => '17', 'butir_pernyataan' => 'Pesantren memiliki ruang organisasi santri'],
                    ['no_sk' => '', 'nomor_butir' => '18', 'butir_pernyataan' => 'Pesantren memiliki laboratorium bahasa dan ruang micro teaching'],
                    ['no_sk' => '', 'nomor_butir' => '19', 'butir_pernyataan' => 'Pesantren memiliki rumah dinas mudir pesantren'],
                    ['no_sk' => '', 'nomor_butir' => '20', 'butir_pernyataan' => 'Pesantren memiliki ruang tamu'],
                    ['no_sk' => '', 'nomor_butir' => '21', 'butir_pernyataan' => 'Pesantren memiliki rumah ustadz pesantren'],
                    ['no_sk' => '', 'nomor_butir' => '22', 'butir_pernyataan' => 'Pesantren memiliki Poskestren'],
                ]
            ],
        ];

        foreach ($komponens as $komponenData) {
            $komponen = MasterEdpmKomponen::create([
                'nama' => $komponenData['nama']
            ]);

            foreach ($komponenData['butirs'] as $butirData) {
                MasterEdpmButir::create([
                    'komponen_id' => $komponen->id,
                    'no_sk' => $butirData['no_sk'],
                    'nomor_butir' => $butirData['nomor_butir'],
                    'butir_pernyataan' => $butirData['butir_pernyataan']
                ]);
            }
        }

        $this->command->info('Master EDPM data seeded successfully!');
    }
}
