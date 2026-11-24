<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Room::firstOrCreate(
            ['name' => 'Sekretariat BEM UPNVJ'],
            [
                'location' => 'Sekretariat BEM UPNVJ',
                'description' => 'Ruang sekretariat bersama untuk kegiatan seluruh bidang dan biro BEM UPNVJ.',
            ]
        );

        $defaultPassword = Hash::make(env('SEED_USER_PASSWORD', 'password'));

        $accounts = [
            [
                'name' => 'Admin Biro SPPI',
                'email' => 'sppi@sekata.test',
                'unit_name' => 'Biro SPPI',
                'unit_type' => 'biro',
                'role' => 'admin',
            ],
            [
                'name' => 'Bidang Minat dan Bakat',
                'email' => 'minatbakat@sekata.test',
                'unit_name' => 'Bidang Minat dan Bakat',
                'unit_type' => 'bidang',
                'role' => 'unit',
            ],
            [
                'name' => 'Bidang Sosial dan Politik',
                'email' => 'sospol@sekata.test',
                'unit_name' => 'Bidang Sosial dan Politik',
                'unit_type' => 'bidang',
                'role' => 'unit',
            ],
            [
                'name' => 'Bidang Kemahasiswaan',
                'email' => 'kemahasiswaan@sekata.test',
                'unit_name' => 'Bidang Kemahasiswaan',
                'unit_type' => 'bidang',
                'role' => 'unit',
            ],
            [
                'name' => 'Bidang Pemberdayaan Perempuan dan Perlindungan Mahasiswa',
                'email' => 'pppm@sekata.test',
                'unit_name' => 'Bidang Pemberdayaan Perempuan dan Perlindungan Mahasiswa',
                'unit_type' => 'bidang',
                'role' => 'unit',
            ],
            [
                'name' => 'Bidang Lingkungan Masyarakat',
                'email' => 'lingmas@sekata.test',
                'unit_name' => 'Bidang Lingkungan Masyarakat',
                'unit_type' => 'bidang',
                'role' => 'unit',
            ],
            [
                'name' => 'Bidang Pengkeima',
                'email' => 'pengkeima@sekata.test',
                'unit_name' => 'Bidang Pengkeima',
                'unit_type' => 'bidang',
                'role' => 'unit',
            ],
            [
                'name' => 'Bidang Ekraf',
                'email' => 'ekraf@sekata.test',
                'unit_name' => 'Bidang Ekraf',
                'unit_type' => 'bidang',
                'role' => 'unit',
            ],
            [
                'name' => 'Biro Kesekretariatan',
                'email' => 'kesekretariatan@sekata.test',
                'unit_name' => 'Biro Kesekretariatan',
                'unit_type' => 'biro',
                'role' => 'unit',
            ],
            [
                'name' => 'Biro Kebendaharaan',
                'email' => 'kebendaharaan@sekata.test',
                'unit_name' => 'Biro Kebendaharaan',
                'unit_type' => 'biro',
                'role' => 'unit',
            ],
            [
                'name' => 'Biro Medkominfo',
                'email' => 'medkominfo@sekata.test',
                'unit_name' => 'Biro Medkominfo',
                'unit_type' => 'biro',
                'role' => 'unit',
            ],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'unit_name' => $account['unit_name'],
                    'unit_type' => $account['unit_type'],
                    'role' => $account['role'],
                    'password' => $defaultPassword,
                ]
            );
        }
    }
}