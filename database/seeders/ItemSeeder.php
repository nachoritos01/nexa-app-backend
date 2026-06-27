<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        // Template data: replace with your own items when setting up a new business
        $items = [
            [
                'name' => 'Sample Item A',
                'description' => 'A sample item for demonstration purposes.',
                'tags' => ['sample'],
                'photos' => [],
            ],
            [
                'name' => 'Sample Item B',
                'description' => 'Another sample item for demonstration purposes.',
                'tags' => ['sample'],
                'photos' => [],
            ],
        ];

        foreach ($items as $item) {
            Item::firstOrCreate(
                ['name' => $item['name']],
                $item
            );
        }

        $this->command->info('Items seeded: ' . count($items) . ' items');
    }
}
