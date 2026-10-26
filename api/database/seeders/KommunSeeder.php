<?php

namespace Database\Seeders;

use App\Models\Kommun;
use App\Models\Lan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Sveriges 290 kommuner från SCB (database/data/kommuner.json).
 * Koordinater (för SMHI-vädret) finns i filen för Dalarnas kommuner; övriga är null tills vidare.
 *
 * Slug: Str::slug(namn). Krockar namnen efter translitterering (Håbo/Habo → "habo")
 * får bägge länet som suffix: "habo-uppsala" och "habo-jonkopings".
 */
class KommunSeeder extends Seeder
{
    public function run(): void
    {
        $lan = Lan::all()->keyBy('kod');

        $rows = collect(File::json(database_path('data/kommuner.json')))
            ->map(fn (array $row) => [
                'lan_id' => $lan[$row['lan_kod']]->id,
                'lan_namn' => $lan[$row['lan_kod']]->namn,
                'namn' => $row['namn'],
                'kod' => $row['kod'],
                'slug' => Str::slug($row['namn']),
                'lat' => $row['lat'],
                'lng' => $row['lng'],
            ]);

        $duplicates = $rows->countBy('slug')->filter(fn (int $count) => $count > 1)->keys();

        $rows = $rows
            ->map(function (array $row) use ($duplicates) {
                if ($duplicates->contains($row['slug'])) {
                    $row['slug'] .= '-'.Str::slug(Str::before($row['lan_namn'], ' län'));
                }
                unset($row['lan_namn']);

                return $row;
            })
            ->all();

        Kommun::upsert($rows, uniqueBy: ['kod'], update: ['lan_id', 'namn', 'slug', 'lat', 'lng']);
    }
}
