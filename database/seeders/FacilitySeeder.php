<?php

namespace Database\Seeders;

use App\Models\Facility;
use Illuminate\Database\Seeder;

class FacilitySeeder extends Seeder
{
    public function run(): void
    {
        $facilities = [
            [
                'name' => 'Proyektor LCD',
                'description' => 'Proyektor LCD ruang sekretariat untuk kebutuhan presentasi dan rapat.',
            ],
            [
                'name' => 'Sound System & Mikrofon',
                'description' => 'Perangkat pengeras suara dan mikrofon untuk forum atau diskusi internal.',
            ],
            [
                'name' => 'Papan Tulis & Spidol',
                'description' => 'Papan tulis putih beserta spidol.',
            ],
            [
                'name' => 'Kursi Lipat Tambahan',
                'description' => 'Kursi lipat tambahan untuk peserta rapat berkapasitas besar.',
            ],
            [
                'name' => 'Kabel Roll & HDMI',
                'description' => 'Kabel terminal listrik dan kabel HDMI cadangan.',
            ],
        ];

        foreach ($facilities as $facility) {
            Facility::firstOrCreate(
                ['name' => $facility['name']],
                ['description' => $facility['description']]
            );
        }
    }
}