<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class categorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            $categories = ['Food','Travel','Health','Rent','Entertainment','Shopping'];
        foreach($categories as $category){
            Category::firstOrCreate(['name'=>$category,'user_id'=>1]);
        }
    }
}
