<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Pradhan Sebak (Super Admin)',
                'email' => 'admin@temple.local',
                'password' => 'Admin@123456',
                'phone' => '+8801700000001',
                'status' => 'active',
                'preferred_locale' => 'bn',
                'address' => 'Central Temple Complex, Main Road',
                'role' => 'super_admin',
            ],
            [
                'name' => 'Mandir Karjonirbahi (Temple Admin)',
                'email' => 'manager@temple.local',
                'password' => 'Manager@123456',
                'phone' => '+8801700000002',
                'status' => 'active',
                'preferred_locale' => 'bn',
                'address' => 'Temple Office Building',
                'role' => 'temple_admin',
            ],
            [
                'name' => 'Koshadhyaksha (Treasurer)',
                'email' => 'treasurer@temple.local',
                'password' => 'Treasurer@123456',
                'phone' => '+8801700000003',
                'status' => 'active',
                'preferred_locale' => 'en',
                'address' => 'Finance & Accounts Desk',
                'role' => 'treasurer',
            ],
            [
                'name' => 'Dr. Anupam Mukherjee (Committee Member)',
                'email' => 'member@temple.local',
                'password' => 'Member@123456',
                'phone' => '+8801700000004',
                'status' => 'active',
                'preferred_locale' => 'bn',
                'address' => 'Sector 4, Temple Community Area',
                'role' => 'committee_member',
            ],
            [
                'name' => 'Joydeb Das (Temple Volunteer)',
                'email' => 'volunteer@temple.local',
                'password' => 'Volunteer@123456',
                'phone' => '+8801700000005',
                'status' => 'active',
                'preferred_locale' => 'bn',
                'address' => 'Bhog Seva Wing',
                'role' => 'volunteer',
            ],
            [
                'name' => 'Subir Roy (Devotee / Member)',
                'email' => 'devotee@temple.local',
                'password' => 'Devotee@123456',
                'phone' => '+8801700000006',
                'status' => 'active',
                'preferred_locale' => 'en',
                'address' => 'Road 12, Devotee Enclave',
                'role' => 'devotee',
            ],
        ];

        foreach ($users as $userData) {
            $roleSlug = $userData['role'];
            unset($userData['role']);

            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password']),
                    'phone' => $userData['phone'],
                    'status' => $userData['status'],
                    'preferred_locale' => $userData['preferred_locale'],
                    'address' => $userData['address'],
                    'email_verified_at' => now(),
                ]
            );

            $role = Role::where('slug', $roleSlug)->first();
            if ($role) {
                $user->roles()->sync([$role->id]);
            }
        }
    }
}
