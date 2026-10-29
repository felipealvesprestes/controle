<?php

namespace Database\Seeders;

use App\Domain\Shared\Color;
use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Creates the spreadsheet's categories, all billable (F-04). Runs only on an empty table,
 * so it never brings back a category that was renamed or deleted.
 */
class CategorySeeder extends Seeder
{
    /** @var array<string, Color> */
    public const array SPREADSHEET = [
        'Desenvolvimento' => Color::Petrol,
        'Reunião' => Color::Blue,
        'Bugfix' => Color::Red,
        'Planejamento' => Color::Violet,
        'Outros' => Color::Slate,
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Category::query()->exists()) {
            return;
        }

        $position = 1;

        foreach (self::SPREADSHEET as $name => $color) {
            (new Category)->forceFill(['name' => $name, 'color' => $color->value, 'billable' => true, 'sort_order' => $position++])->save();
        }
    }
}
