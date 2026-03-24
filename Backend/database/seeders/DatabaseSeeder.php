<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Cylinder;
use App\Models\Employee;
use App\Models\Status;
use App\Models\Transaction;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $statuses = collect([
            'noliktava',
            'pie klienta',
            'tukss',
            'pilns',
            'remonta',
        ])->mapWithKeys(fn (string $name) => [
            $name => Status::firstOrCreate(['name' => $name]),
        ]);

        $admin = Employee::firstOrCreate(
            ['email' => 'admin@gaso.lv'],
            [
                'name' => 'Anna',
                'surname' => 'Admina',
                'password' => 'password',
                'phone' => '+37120000001',
                'role' => 'administrators',
            ],
        );

        $employee = Employee::firstOrCreate(
            ['email' => 'darbinieks@gaso.lv'],
            [
                'name' => 'Janis',
                'surname' => 'Darbinieks',
                'password' => 'password',
                'phone' => '+37120000002',
                'role' => 'darbinieks',
            ],
        );

        $clientA = Client::firstOrCreate(
            ['email' => 'klients1@gaso.lv'],
            [
                'name' => 'Ilze',
                'surname' => 'Kalnina',
                'password' => 'password',
                'phone' => '+37120000011',
                'street' => 'Brivibas iela',
                'home_number' => 15,
                'flat_number' => 3,
                'city' => 'Riga',
                'zip_code' => 'LV-1010',
                'username' => 'klients1',
            ],
        );

        $clientB = Client::firstOrCreate(
            ['email' => 'klients2@gaso.lv'],
            [
                'name' => 'Maris',
                'surname' => 'Ozols',
                'password' => 'password',
                'phone' => '+37120000012',
                'street' => 'Liepajas iela',
                'home_number' => 24,
                'flat_number' => null,
                'city' => 'Jelgava',
                'zip_code' => 'LV-3001',
                'username' => 'klients2',
            ],
        );

        $cylinders = [
            [
                'status_id' => $statuses['noliktava']->id,
                'serial_number' => 'GAS-001',
                'capacity' => 11.00,
                'manufacture_date' => '2022-04-10',
                'inspection_date' => now()->addDays(12)->toDateString(),
                'notes' => 'Pieejams noliktava.',
            ],
            [
                'status_id' => $statuses['pilns']->id,
                'serial_number' => 'GAS-002',
                'capacity' => 27.00,
                'manufacture_date' => '2021-02-15',
                'inspection_date' => now()->addDays(45)->toDateString(),
                'notes' => 'Pilns balons pie izsniegsanas zonas.',
            ],
            [
                'status_id' => $statuses['pie klienta']->id,
                'serial_number' => 'GAS-003',
                'capacity' => 50.00,
                'manufacture_date' => '2020-07-01',
                'inspection_date' => now()->addDays(20)->toDateString(),
                'notes' => 'Aktivs klienta balons.',
            ],
        ];

        foreach ($cylinders as $data) {
            Cylinder::firstOrCreate(['serial_number' => $data['serial_number']], $data);
        }

        $issuedCylinder = Cylinder::where('serial_number', 'GAS-003')->first();

        if ($issuedCylinder && ! Transaction::where('cylinder_id', $issuedCylinder->id)->exists()) {
            Transaction::create([
                'cylinder_id' => $issuedCylinder->id,
                'client_id' => $clientA->id,
                'employee_id' => $employee->id,
                'issue_date' => now()->subDays(5)->toDateString(),
                'return_date' => null,
                'action_type' => 'izsniegts',
            ]);
        }

        $returnedCylinder = Cylinder::where('serial_number', 'GAS-002')->first();

        if ($returnedCylinder && Transaction::where('cylinder_id', $returnedCylinder->id)->count() === 0) {
            Transaction::create([
                'cylinder_id' => $returnedCylinder->id,
                'client_id' => $clientB->id,
                'employee_id' => $admin->id,
                'issue_date' => now()->subDays(10)->toDateString(),
                'return_date' => null,
                'action_type' => 'izsniegts',
            ]);

            Transaction::create([
                'cylinder_id' => $returnedCylinder->id,
                'client_id' => $clientB->id,
                'employee_id' => $admin->id,
                'issue_date' => now()->subDays(10)->toDateString(),
                'return_date' => now()->subDays(2)->toDateString(),
                'action_type' => 'atgriezts',
            ]);
        }
    }
}
