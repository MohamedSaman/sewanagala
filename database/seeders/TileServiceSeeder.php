<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TileService;

class TileServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['name' => '2x2 Double Noising', 'code' => 'SERV-2X2-DN', 'price' => 500.00],
            ['name' => '2x4 Double Noising', 'code' => 'SERV-2X4-DN', 'price' => 550.00],
            ['name' => '2x2 Router & polish', 'code' => 'SERV-2X2-RP', 'price' => 150.00],
            ['name' => '2x4 Router & polish', 'code' => 'SERV-2X4-RP', 'price' => 200.00],
            ['name' => '2x2 Router', 'code' => 'SERV-2X2-RT', 'price' => 60.00],
            ['name' => '2x4 Router', 'code' => 'SERV-2X4-RT', 'price' => 80.00],
            ['name' => '2x2 Cutting', 'code' => 'SERV-2X2-CT', 'price' => 40.00],
            ['name' => '2x4 Cutting', 'code' => 'SERV-2X4-CT', 'price' => 50.00],
        ];

        foreach ($services as $service) {
            TileService::updateOrCreate(
                ['name' => $service['name']],
                [
                    'code' => $service['code'],
                    'price' => $service['price'],
                    'is_active' => true,
                ]
            );
        }
    }
}
