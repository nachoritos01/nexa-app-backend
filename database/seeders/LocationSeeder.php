<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        Location::updateOrCreate(
            ['name' => 'Main Location'],
            [
                'address' => 'Main business address',
                'city' => 'City',
                'state' => 'ST',
                'zip' => '00000',
                'phone' => '0000000000',
                'schedule' => 'Mon-Fri 9:00-18:00',
                'lat' => null,
                'lng' => null,
                'is_active' => true,
            ],
        );
    }
}
