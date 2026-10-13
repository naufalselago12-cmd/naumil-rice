<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Store;
use Illuminate\Support\Facades\Hash;

class MultiStoreSuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Update existing stores with clean slugs
        $sudirman = Store::where('code', 'NR-SDR')->orWhere('id', 1)->first();
        if ($sudirman) {
            $sudirman->update(['slug' => 'sudirman']);
        }

        $tebet = Store::where('code', 'NR-TBT')->orWhere('id', 2)->first();
        if ($tebet) {
            $tebet->update(['slug' => 'tebet']);
        }

        // 2. Create / Update Super Admin
        User::updateOrCreate(
            ['username' => 'naufal_su'],
            [
                'name' => 'Super Administrator Naufal',
                'email' => 'naufal_su@naumilrice.com',
                'password' => Hash::make('naufal_super2020'),
                'role' => User::ROLE_SUPERADMIN,
                'store_id' => null,
            ]
        );

        // 3. Create / Update Store Admins for each store
        if ($sudirman) {
            User::updateOrCreate(
                ['username' => 'admin_sudirman'],
                [
                    'name' => 'Admin Sudirman Branch',
                    'email' => 'admin.sudirman@naumilrice.com',
                    'password' => Hash::make('admin12345'),
                    'role' => User::ROLE_STORE_ADMIN,
                    'store_id' => $sudirman->id,
                ]
            );
        }

        if ($tebet) {
            User::updateOrCreate(
                ['username' => 'admin_tebet'],
                [
                    'name' => 'Admin Tebet Kitchen',
                    'email' => 'admin.tebet@naumilrice.com',
                    'password' => Hash::make('admin12345'),
                    'role' => User::ROLE_STORE_ADMIN,
                    'store_id' => $tebet->id,
                ]
            );
        }
    }
}
