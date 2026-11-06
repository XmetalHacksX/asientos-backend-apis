<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\LayoutObject;
use App\Models\Node;
use App\Models\Space;
use Illuminate\Database\Seeder;

class SpaceAndLayoutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $building = Building::where('code', 'AUD-CHET')->first();

        if (! $building) {
            return;
        }

        $space = Space::firstOrCreate(
            [
                'building_id' => $building->id,
                'name' => 'Teatro Principal de las Artes',
            ],
            [
                'viewport' => [
                    'zoom' => 1.0,
                    'initial_x' => 100,
                    'initial_y' => 50,
                ],
                'is_template' => true,
            ]
        );

        // Stage decorative / layout object
        LayoutObject::firstOrCreate(
            [
                'space_id' => $space->id,
                'type' => 'stage',
            ],
            [
                'properties' => [
                    'x' => 150,
                    'y' => 50,
                    'width' => 700,
                    'height' => 100,
                    'color' => '#334155',
                    'text' => 'ESCENARIO PRINCIPAL',
                    'rotation' => 0,
                ],
            ]
        );

        // Create 100 seats (5 rows A-E x 20 seats)
        $rows = ['A', 'B', 'C', 'D', 'E'];
        $cols = 20;
        $startX = 100;
        $startY = 200;
        $gapX = 40;
        $gapY = 50;

        $nodes = [];
        $now = now();

        foreach ($rows as $rowIndex => $rowLetter) {
            for ($col = 1; $col <= $cols; $col++) {
                $paddedCol = str_pad((string) $col, 2, '0', STR_PAD_LEFT);
                $identifier = "{$rowLetter}-{$paddedCol}";

                // Check if already exists to avoid unique constraint duplicates
                $exists = Node::where('space_id', $space->id)->where('identifier', $identifier)->exists();
                if (! $exists) {
                    $nodes[] = [
                        'space_id' => $space->id,
                        'identifier' => $identifier,
                        'pos_x' => $startX + (($col - 1) * $gapX),
                        'pos_y' => $startY + ($rowIndex * $gapY),
                        'status' => 'active',
                        'is_occupied' => false,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        if (! empty($nodes)) {
            Node::insert($nodes);
        }
    }
}
