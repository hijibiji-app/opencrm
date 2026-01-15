<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create();

        $projects = [
            [
                'name' => 'Premium E-commerce',
                'platform' => 'Laravel & Vue JS',
                'category' => 'Ecommerce',
                'domain' => 'https://premium-shop.test',
                'status' => 'active',
                'description' => 'A high-end e-commerce platform built with Laravel and Vue JS.',
                'user_id' => $user->id,
            ],
            [
                'name' => 'Daily Shop',
                'platform' => 'Laravel & Vue JS',
                'category' => 'Ecommerce',
                'domain' => 'https://daily-shop.com',
                'status' => 'active',
                'description' => 'Grocery delivery application.',
                'user_id' => $user->id,
            ],
            [
                'name' => 'Corporate Hub',
                'platform' => 'WordPress',
                'category' => 'Portfolio',
                'domain' => 'https://corporate-hub.com',
                'status' => 'active',
                'description' => 'Main corporate website.',
                'user_id' => $user->id,
            ],
            [
                'name' => 'EduLink LMS',
                'platform' => 'Laravel & Nuxt JS',
                'category' => 'LMS',
                'domain' => 'https://edulink-lms.test',
                'status' => 'active',
                'description' => 'Modern learning management system.',
                'user_id' => $user->id,
            ],
            [
                'name' => 'Inventory SaaS',
                'platform' => 'Laravel & Next JS',
                'category' => 'SaaS',
                'domain' => 'https://inventory-pro.io',
                'status' => 'active',
                'description' => 'Inventory management for small businesses.',
                'user_id' => $user->id,
            ],
            [
                'name' => 'B2B Marketplace',
                'platform' => 'Laravel & Livewire',
                'category' => 'Multi Vendor',
                'domain' => 'https://b2b-market.test',
                'status' => 'active',
                'description' => 'Trade platform for wholesalers.',
                'user_id' => $user->id,
            ],
            [
                'name' => 'Simple Blog',
                'platform' => 'Laravel & Blade',
                'category' => 'Blog',
                'domain' => 'https://myblog-demo.test',
                'status' => 'active',
                'description' => 'Personal blog system.',
                'user_id' => $user->id,
            ],
        ];

        // Clear existing to avoid duplicates if re-running
        Project::truncate();

        foreach ($projects as $project) {
            Project::create($project);
        }
    }
}
