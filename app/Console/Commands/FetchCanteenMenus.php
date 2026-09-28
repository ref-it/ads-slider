<?php

namespace App\Console\Commands;

use App\Models\Canteen;
use App\Services\CanteenMenuParser;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FetchCanteenMenus extends Command implements Isolatable
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'canteens:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrapes today\'s Speiseplan for every configured canteen and caches it to a json file';

    /**
     * Execute the console command.
     */
    public function handle(CanteenMenuParser $scraper): void
    {
        $canteens = Canteen::whereHas('schedule', fn ($q) => $q->where('disabled', false))->get();

        if ($canteens->isEmpty()) {
            $this->info('No enabled canteens configured.');

            return;
        }

        $date = now()->format('d.m.Y');

        foreach ($canteens as $canteen) {
            foreach ($canteen->externalIdsByLocale() as $locale => $externalId) {
                $this->info("Fetching menu for '{$canteen->name}' [{$locale}] (external_id={$externalId})…");

                try {
                    $menu = $scraper->fetch($externalId, $date);
                } catch (\Throwable $e) {
                    $this->error("Failed to fetch [{$locale}] menu for '{$canteen->name}': {$e->getMessage()}");
                    Log::error("Canteen menu fetch failed for canteen {$canteen->id} [{$locale}]: {$e->getMessage()}");

                    continue;
                }

                Storage::disk('local')->put($canteen->menuCachePath($locale), json_encode($menu));
                $this->info('Stored '.count($menu['lunch']).' lunch and '.count($menu['dinner']).' dinner items.');
            }
        }
    }
}
