<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoClientSeeder extends Seeder
{
    public function run(): void
    {
        $client = Client::firstOrCreate(
            ['name' => 'Demo Behavioral Health'],
            [
                'legal_name' => 'Demo Behavioral Health LLC',
                'tax_id'     => '12-3456789',
                'phone'      => '(305) 555-0100',
                'email'      => 'contact@demo-bh.local',
                'address'    => '100 Main St',
                'city'       => 'Miami',
                'state'      => 'FL',
                'zip'        => '33101',
                'active'     => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@demo-bh.local'],
            [
                'client_id' => $client->id,
                'name'      => 'Demo Admin',
                'password'  => Hash::make('admin123'),
                'active'    => true,
            ]
        )->syncRoles(['Client Admin']);

        User::updateOrCreate(
            ['email' => 'clinical-admin@demo-bh.local'],
            [
                'client_id' => $client->id,
                'name'      => 'Clinical Admin',
                'password'  => Hash::make('clinical123'),
                'active'    => true,
            ]
        )->syncRoles(['Clinical Admin']);
    }
}
