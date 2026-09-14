<?php

namespace Database\Seeders;

use App\Support\Library\LibraryBackfill;
use Illuminate\Database\Seeder;

class PackageLibrarySeeder extends Seeder
{
    public function run(): void
    {
        (new LibraryBackfill)->run();
    }
}
