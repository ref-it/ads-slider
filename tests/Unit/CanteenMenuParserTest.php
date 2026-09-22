<?php

namespace Tests\Unit;

use App\Services\CanteenMenuParser;
use PHPUnit\Framework\TestCase;

class CanteenMenuParserTest extends TestCase
{
    private function fixture(): string
    {
        return <<<'HTML'
        <div class="splGroupWrapper">
            <div class="rowMealInner">
                <div class="mealText">mensaVital: Gemüsecurry mit Reis !</div>
                <div class="zusatzstoffe">Zusatzstoffe: 1,4</div>
                <div class="allergene">Allergene: Wz,Mi</div>
                <div class="mealPreise">2,20 / 4,40 / 6,60 €</div>
                <img class="splIconMeal" alt="Vegetarische Speisen (V)">
                <img class="splIconMeal" alt="Vegane Speisen (V*)">
            </div>
            <div class="rowMealInner">
                <div class="mealText">Schnitzel mit Pommes</div>
                <div class="zusatzstoffe"></div>
                <div class="allergene">Allergene: Wz</div>
                <div class="mealPreise">3,10 / 5,30 / 7,50 €</div>
            </div>
        </div>
        HTML;
    }

    public function test_parses_lunch_meals_from_the_first_group(): void
    {
        $result = (new CanteenMenuParser)->parse($this->fixture());

        $this->assertCount(2, $result['lunch']);
        $this->assertCount(0, $result['dinner']);

        $first = $result['lunch'][0];
        $this->assertEquals('Gemüsecurry mit Reis', $first['name']);
        $this->assertEquals(['1', '4'], $first['additives']);
        $this->assertEquals(['Wz', 'Mi'], $first['allergens']);
        $this->assertEquals(['students' => 2.2, 'employees' => 4.4, 'guests' => 6.6], $first['prices']);
        $this->assertTrue($first['isVegetarian']);
        $this->assertTrue($first['isVegan']);
    }

    public function test_meal_without_additives_or_icons_gets_sensible_defaults(): void
    {
        $result = (new CanteenMenuParser)->parse($this->fixture());

        $second = $result['lunch'][1];
        $this->assertEquals('Schnitzel mit Pommes', $second['name']);
        $this->assertEquals([], $second['additives']);
        $this->assertEquals(['Wz'], $second['allergens']);
        $this->assertFalse($second['isVegetarian']);
        $this->assertFalse($second['isVegan']);
    }

    public function test_second_group_is_treated_as_dinner(): void
    {
        $html = <<<'HTML'
        <div class="splGroupWrapper">
            <div class="rowMealInner">
                <div class="mealText">Lunch Dish</div>
                <div class="mealPreise">1,00 / 2,00 / 3,00 €</div>
            </div>
        </div>
        <div class="splGroupWrapper">
            <div class="rowMealInner">
                <div class="mealText">Dinner Dish</div>
                <div class="mealPreise">1,50 / 2,50 / 3,50 €</div>
            </div>
        </div>
        HTML;

        $result = (new CanteenMenuParser)->parse($html);

        $this->assertCount(1, $result['lunch']);
        $this->assertCount(1, $result['dinner']);
        $this->assertEquals('Lunch Dish', $result['lunch'][0]['name']);
        $this->assertEquals('Dinner Dish', $result['dinner'][0]['name']);
    }

    public function test_name_prefixes_are_stripped(): void
    {
        $html = <<<'HTML'
        <div class="splGroupWrapper">
            <div class="rowMealInner">
                <div class="mealText">mensaInternational Thailand: Pad Thai</div>
                <div class="mealPreise">1,00 / 2,00 / 3,00 €</div>
            </div>
            <div class="rowMealInner">
                <div class="mealText">Burgertag Cheeseburger</div>
                <div class="mealPreise">1,00 / 2,00 / 3,00 €</div>
            </div>
        </div>
        HTML;

        $result = (new CanteenMenuParser)->parse($html);

        $this->assertEquals('Pad Thai', $result['lunch'][0]['name']);
        $this->assertEquals('Cheeseburger', $result['lunch'][1]['name']);
    }
}
