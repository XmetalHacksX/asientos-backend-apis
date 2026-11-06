<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Campus;
use Illuminate\Database\Seeder;

class CampusAndBuildingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $campusChetumal = Campus::firstOrCreate(
            ['code' => 'CHETUMAL'],
            ['name' => 'Campus Chetumal Bahía']
        );

        Building::firstOrCreate(
            ['campus_id' => $campusChetumal->id, 'code' => 'AUD-CHET'],
            ['name' => 'Edificio A - Auditorio Central']
        );

        Building::firstOrCreate(
            ['campus_id' => $campusChetumal->id, 'code' => 'CC-CHET'],
            ['name' => 'Edificio B - Centro Cultural y Artístico']
        );

        $campusCancun = Campus::firstOrCreate(
            ['code' => 'CANCUN'],
            ['name' => 'Campus Cancún']
        );

        Building::firstOrCreate(
            ['campus_id' => $campusCancun->id, 'code' => 'POLI-CUN'],
            ['name' => 'Edificio Polifuncional de Usos Múltiples']
        );
    }
}
