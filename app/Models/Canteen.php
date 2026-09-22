<?php

namespace App\Models;

use App\Services\CanteenMenuParser;
use App\Traits\RealmTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

class Canteen extends Model
{
    use HasFactory, RealmTrait;

    protected $fillable = ['name', 'external_id', 'external_id_en'];

    protected $hidden = ['created_at', 'updated_at', 'user_id', 'pivot', 'realm_id'];

    protected $appends = ['menu'];

    /**
     * The menu scraped by canteens:fetch, cached on the local disk, in the
     * current app locale (already resolved per-monitor by the time this is
     * read - see MonitorController::getLocale()), with additive/allergen
     * codes resolved to labels in that same locale. Null until the first
     * successful fetch for this canteen/locale.
     */
    public function getMenuAttribute(): ?array
    {
        return $this->menuForLocale(App::getLocale());
    }

    public function menuForLocale(string $locale): ?array
    {
        $path = $this->menuCachePath($locale);
        if (! Storage::disk('local')->exists($path)) {
            // stw-thueringen.de only has German/English editions; fall back
            // to the default (German) cache for any other locale, or if no
            // English resources_id/cache is available yet.
            $path = $this->menuCachePath(null);
        }

        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        $menu = json_decode(Storage::disk('local')->get($path), true);

        foreach (['lunch', 'dinner'] as $service) {
            // Note: iterating "$menu[$service] ?? [] as &$meal" would silently
            // mutate a throwaway copy produced by "??" rather than $menu
            // itself - the meals must be pulled into a real variable first.
            $meals = $menu[$service] ?? [];
            foreach ($meals as &$meal) {
                $meal['additives'] = array_map(fn ($code) => CanteenMenuParser::additiveLabel($code), $meal['additives'] ?? []);
                $meal['allergens'] = array_map(fn ($code) => CanteenMenuParser::allergenLabel($code), $meal['allergens'] ?? []);
            }
            unset($meal);
            $menu[$service] = $meals;
        }

        return $menu;
    }

    /**
     * @return array<string, int> locale => resources_id, for every locale
     *                             this canteen has an external ID for.
     */
    public function externalIdsByLocale(): array
    {
        $ids = ['de' => $this->external_id];
        if ($this->external_id_en) {
            $ids['en'] = $this->external_id_en;
        }

        return $ids;
    }

    public function menuCachePath(?string $locale): string
    {
        return $locale === 'en' ? "canteen-{$this->id}-en.json" : "canteen-{$this->id}.json";
    }

    public function schedule()
    {
        return $this->morphOne(Schedule::class, 'scheduleable');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function monitors(): BelongsToMany
    {
        return $this->belongsToMany(Monitor::class);
    }

    protected static function booted()
    {
        static::updated(function (Canteen $canteen) {
            if ($canteen->isDirty('realm_id')) {
                $canteen->schedule()->update(['realm_id' => $canteen->realm_id]);
            }
        });

        static::deleted(function (Canteen $canteen) {
            $canteen->schedule()->delete();
            Storage::disk('local')->delete([
                $canteen->menuCachePath('de'),
                $canteen->menuCachePath('en'),
            ]);
        });
    }
}
