<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;
use App\Models\Customer;
use Faker\Factory as Faker;

class ServiceSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();
        $customers = Customer::all();

        foreach ($customers as $customer) {
            for ($i = 0; $i < 10; $i++) {
                Service::create([
                    'customer_id' => $customer->id,
                    'name' => $faker->word,
                    'description' => $faker->sentence,
                    'price' => $faker->randomFloat(2, 10, 1000)
                ]);
            }
        }
    }
}
