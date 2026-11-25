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

        // Development-only credentials (each account uses <prefix>123 unless overridden via SEED_USER_PASSWORD).
        // Must be rotated before any BEM pilot.
        $accounts = [
            [
                'name' => 'Admin Biro SPPI',
                'email' => 'sppi@sekata.test',
                'unit_name' => 'Biro SPPI',
                'unit_type' => 'biro',
                'role' => 'admin',
                'password' => 'sppi123',
            ],
            [
                'name' => 'Bidang Minat dan Bakat',
                'email' => 'minatbakat@sekata.test',
                'unit_name' => 'Bidang Minat dan Bakat',
                'unit_type' => 'bidang',
                'role' => 'unit',
                'password' => 'minatbakat123',
            ],
            [
                'name' => 'Bidang Sosial dan Politik',
                'email' => 'sospol@sekata.test',
                'unit_name' => 'Bidang Sosial dan Politik',
                'unit_type' => 'bidang',
                'role' => 'unit',
                'password' => 'sospol123',
            ],
            [
                'name' => 'Bidang Kemahasiswaan',
                'email' => 'kemahasiswaan@sekata.test',
                'unit_name' => 'Bidang Kemahasiswaan',
                'unit_type' => 'bidang',
                'role' => 'unit',
                'password' => 'kemahasiswaan123',
            ],
            [
                'name' => 'Bidang Pemberdayaan Perempuan dan Perlindungan Mahasiswa',
                'email' => 'pppm@sekata.test',
                'unit_name' => 'Bidang Pemberdayaan Perempuan dan Perlindungan Mahasiswa',
                'unit_type' => 'bidang',
                'role' => 'unit',
                'password' => 'pppm123',
            ],
            [
                'name' => 'Bidang Lingkungan Masyarakat',
                'email' => 'lingmas@sekata.test',
                'unit_name' => 'Bidang Lingkungan Masyarakat',
                'unit_type' => 'bidang',
                'role' => 'unit',
                'password' => 'lingmas123',
            ],
            [
                'name' => 'Bidang Pengkeima',
                'email' => 'pengkeima@sekata.test',
                'unit_name' => 'Bidang Pengkeima',
                'unit_type' => 'bidang',
                'role' => 'unit',
                'password' => 'pengkeima123',
            ],
            [
                'name' => 'Bidang Ekraf',
                'email' => 'ekraf@sekata.test',
                'unit_name' => 'Bidang Ekraf',
                'unit_type' => 'bidang',
                'role' => 'unit',
                'password' => 'ekraf123',
            ],
            [
                'name' => 'Biro Kesekretariatan',
                'email' => 'kesekretariatan@sekata.test',
                'unit_name' => 'Biro Kesekretariatan',
                'unit_type' => 'biro',
                'role' => 'unit',
                'password' => 'kesekretariatan123',
            ],
            [
                'name' => 'Biro Kebendaharaan',
                'email' => 'kebendaharaan@sekata.test',
                'unit_name' => 'Biro Kebendaharaan',
                'unit_type' => 'biro',
                'role' => 'unit',
                'password' => 'kebendaharaan123',
            ],
            [
                'name' => 'Biro Medkominfo',
                'email' => 'medkominfo@sekata.test',
                'unit_name' => 'Biro Medkominfo',
                'unit_type' => 'biro',
                'role' => 'unit',
                'password' => 'medkominfo123',
            ],
        ];

        $overridePassword = env('SEED_USER_PASSWORD');

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'unit_name' => $account['unit_name'],
                    'unit_type' => $account['unit_type'],
                    'role' => $account['role'],
                    'password' => Hash::make($overridePassword ?: $account['password']),
                ]
            );
        }
    }
}