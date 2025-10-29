<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        for ($i = 1; $i <= 100; $i++) {
            Product::create([
                'name' => $faker->words(3, true),
                'description' => $faker->sentence(12),
                'price' => $faker->randomFloat(2, 50, 1000),
                'stock' => $faker->numberBetween(5, 100),
                'image_url' => "https://picsum.photos/seed/product{$i}/600/400",
            ]);
        }
    }
}
