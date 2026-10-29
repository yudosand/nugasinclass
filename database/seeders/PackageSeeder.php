<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name' => 'All Prodi Package',
                'slug' => 'all-prodi-package',
                'description' => 'Tidak termasuk tugas praktikum',
                'price' => 500000,
                'price_label' => '500k / semester',
                'features' => ['Absen sesi', 'Pengerjaan Diskusi', 'Pengerjaan Tugas Teori'],
                'is_recommended' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Sistem Informasi Package',
                'slug' => 'sistem-informasi-package',
                'description' => 'Full cover semua tugas',
                'price' => 600000,
                'price_label' => '600k / semester',
                'features' => ['Absen sesi', 'Pengerjaan Diskusi', 'Pengerjaan Tugas Teori', 'Tugas Praktikum'],
                'is_recommended' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Tugas Praktikum',
                'slug' => 'tugas-praktikum',
                'description' => 'Only for Sistem Informasi',
                'price' => 200000,
                'price_label' => '200k / tugas',
                'features' => ['Tugas Praktikum', 'Edit Video Tugas Praktikum', 'File siap kirim'],
                'is_recommended' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Kelas Privat / Bimbingan',
                'slug' => 'kelas-privat',
                'description' => 'Bimbingan intensif materi sulit & coding',
                'price' => 50000,
                'price_label' => '50k / sesi',
                'features' => ['Membahas Tugas Praktikum 1 s/d 3', 'Bedah modul & materi sulit', 'Live via Zoom/Gmeet'],
                'is_recommended' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Simulasi Ujian (UAS)',
                'slug' => 'simulasi-uas',
                'description' => 'Latihan soal try out mirip aslinya',
                'price' => 15000,
                'price_label' => '15k / akses',
                'features' => ['Akses ujian CAT + timer realtime', 'Kunci jawaban & pembahasan', 'Dapat mentahan file soal (PDF)'],
                'is_recommended' => false,
                'is_active' => true,
            ],
        ];

        foreach ($packages as $pkg) {
            Package::updateOrCreate(['slug' => $pkg['slug']], $pkg);
        }
    }
}
