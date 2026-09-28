<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Fetches and parses the Speiseplan (canteen menu) HTML fragment served by
 * the Studierendenwerk Thüringen for a given canteen ("resources_id"), e.g.
 * https://www.stw-thueringen.de/xhr/loadspeiseplan.html, using native
 * DOMDocument/DOMXPath so no new Composer dependency is needed.
 */
class CanteenMenuParser
{
    private const ENDPOINT = 'https://www.stw-thueringen.de/xhr/loadspeiseplan.html';

    /**
     * Additive code => English label. Used purely as a translation source
     * string (see lang/{en,de,it}.json) - the cached menu stores raw codes,
     * and labels are resolved via __() at serve time, so the label matches
     * whichever monitor/locale requests the menu rather than being baked
     * into the cache in one fixed language.
     */
    public const ADDITIVES = [
        '1' => 'with colorants',
        '2' => 'with preservatives',
        '3' => 'with antioxidants',
        '4' => 'with flavour enhancers',
        '6' => 'blackening',
        '7' => 'wax (on fruit)',
        '8' => 'with phosphate',
        '9' => 'with sweeteners',
        '10' => 'contains a source of phenylalanine',
        '13' => 'can affect the activity and attention span of children',
        '14' => 'frosting contains cocoa powder',
        '15' => 'contains caffeine',
        '16' => 'contains quinine',
        'T"' => 'contains animal ingredients, unsuitable for vegetarians',
        'T*' => 'contains animal ingredients, unsuitable for vegans',
        'T1' => 'contains animal gelatin',
        'T2' => 'contains animal rennet',
        'T3' => 'contains real carmine',
        'T4' => 'contains squid ink',
        'T5' => 'contains honey',
    ];

    /**
     * Allergen code => English label, see the note on ADDITIVES above.
     */
    public const ALLERGENS = [
        'Wz' => 'contains wheat',
        'Ro' => 'contains rye',
        'Gs' => 'contains barley',
        'Hf' => 'contains oats',
        'Di' => 'contains spelt',
        'Ka' => 'contains kamut (khorasan-wheat)',
        'Kr' => 'contains crustaceans',
        'Ei' => 'contains chicken egg',
        'Fi' => 'contains fish',
        'Er' => 'contains peanuts',
        'So' => 'contains soya',
        'Mi' => 'contains milk and milk sugar',
        'Ma' => 'contains almond',
        'Ha' => 'contains hazelnut',
        'Wa' => 'contains walnut',
        'Ca' => 'contains cashew',
        'Pe' => 'contains pecan',
        'Pa' => 'contains brazil nuts',
        'Pi' => 'contains pistachio',
        'Mc' => 'contains macadamia',
        'Sel' => 'contains celery',
        'Sen' => 'contains mustard',
        'Ses' => 'contains sesame',
        'Su' => 'contains sulfur dioxide/sulfites',
        'Lu' => 'contains lupin',
        'We' => 'contains molluscs',
    ];

    public static function additiveLabel(string $code): string
    {
        return __(self::ADDITIVES[$code] ?? $code);
    }

    public static function allergenLabel(string $code): string
    {
        return __(self::ALLERGENS[$code] ?? $code);
    }

    /**
     * @return array{lunch: array<int, array<string, mixed>>, dinner: array<int, array<string, mixed>>, lastUpdated: int}
     */
    public function fetch(int $externalId, string $date): array
    {
        $response = Http::asForm()->post(self::ENDPOINT, [
            'resources_id' => $externalId,
            'date' => $date,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException("HTTP {$response->status()} while fetching the Speiseplan for canteen {$externalId}");
        }

        return $this->parse($response->body());
    }

    /**
     * @return array{lunch: array<int, array<string, mixed>>, dinner: array<int, array<string, mixed>>, lastUpdated: int}
     */
    public function parse(string $html): array
    {
        $dom = new DOMDocument;
        // Suppress warnings for the malformed/partial HTML fragments these endpoints tend to return.
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($dom);

        $meals = ['lunch' => [], 'dinner' => []];

        foreach ($this->queryByClass($xpath, 'splGroupWrapper') as $groupIndex => $group) {
            $target = $groupIndex === 0 ? 'lunch' : 'dinner';

            foreach ($this->queryByClass($xpath, 'rowMealInner', $group) as $row) {
                $meals[$target][] = $this->parseMealRow($xpath, $row);
            }
        }

        $meals['lastUpdated'] = now()->timestamp;

        return $meals;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseMealRow(DOMXPath $xpath, DOMElement $row): array
    {
        $name = $this->cleanMealName($this->textOf($xpath, 'mealText', $row));

        $additives = $this->splitCodes($this->textOf($xpath, 'zusatzstoffe', $row), 'Zusatzstoffe: ');
        $allergens = $this->splitCodes($this->textOf($xpath, 'allergene', $row), 'Allergene: ');

        $isVegetarian = false;
        $isVegan = false;
        foreach ($this->queryByClass($xpath, 'splIconMeal', $row) as $icon) {
            $alt = $icon->getAttribute('alt');
            if ($alt === 'Vegetarische Speisen (V)') {
                $isVegetarian = true;
            }
            if ($alt === 'Vegane Speisen (V*)') {
                $isVegan = true;
            }
        }

        return [
            'name' => $name,
            'additives' => $additives,
            'allergens' => $allergens,
            'prices' => $this->parsePrices($this->textOf($xpath, 'mealPreise', $row)),
            'isVegetarian' => $isVegetarian,
            'isVegan' => $isVegan,
        ];
    }

    private function cleanMealName(string $name): string
    {
        $name = preg_replace('/(mensaInternational|MensaInternational).*:/u', '', $name) ?? $name;
        foreach (['mensaVital:', 'MensaVital:', 'Burgertag'] as $prefix) {
            if (str_starts_with($name, $prefix)) {
                $name = substr($name, strlen($prefix));
            }
        }
        $name = str_replace('!', '', $name);
        $name = str_replace(' ,', ',', $name);

        return trim($name);
    }

    /**
     * @return string[] raw codes, e.g. ['1', '4'] or ['Wz', 'Mi'] - resolved
     *                   to a label via additiveLabel()/allergenLabel() at
     *                   serve time, not baked in here.
     */
    private function splitCodes(string $text, string $prefix): array
    {
        $text = trim($text);
        if (str_starts_with($text, $prefix)) {
            $text = substr($text, strlen($prefix));
        }
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        return array_map('trim', explode(',', $text));
    }

    /**
     * @return array{students: float, employees: float, guests: float}
     */
    private function parsePrices(string $text): array
    {
        $parts = array_map('trim', explode('/', trim($text)));

        $toFloat = fn (string $value) => (float) str_replace(',', '.', trim(str_replace('€', '', $value)));

        return [
            'students' => isset($parts[0]) ? $toFloat($parts[0]) : 0.0,
            'employees' => isset($parts[1]) ? $toFloat($parts[1]) : 0.0,
            'guests' => isset($parts[2]) ? $toFloat($parts[2]) : 0.0,
        ];
    }

    private function textOf(DOMXPath $xpath, string $class, DOMElement $context): string
    {
        $nodes = $this->queryByClass($xpath, $class, $context);

        return trim($nodes[0]?->textContent ?? '');
    }

    /**
     * @return DOMElement[]
     */
    private function queryByClass(DOMXPath $xpath, string $class, ?DOMElement $context = null): array
    {
        $condition = "contains(concat(' ', normalize-space(@class), ' '), ' {$class} ')";
        $query = $context ? ".//*[{$condition}]" : "//*[{$condition}]";
        $nodeList = $context ? $xpath->query($query, $context) : $xpath->query($query);

        return $nodeList === false ? [] : iterator_to_array($nodeList);
    }
}
