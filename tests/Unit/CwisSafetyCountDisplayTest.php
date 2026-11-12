<?php

namespace Tests\Unit;

use App\Support\CwisValueFormatter;
use Tests\TestCase;

class CwisSafetyCountDisplayTest extends TestCase
{
    public function test_counts_are_whole_numbers_and_unknowns_are_not_zero(): void
    {
        $this->assertSame('13,643', CwisValueFormatter::formatCount('13643.000'));
        $this->assertSame('0', CwisValueFormatter::formatCount('0.000'));
        foreach ([null, 'NaN', 'NA', 'Infinity', '', '-1', '2.5'] as $value) {
            $this->assertSame('No data', CwisValueFormatter::formatCount($value));
        }
        $this->assertSame('4.348', CwisValueFormatter::format('4.347826'));
    }

    public function test_cards_fill_the_flex_parent_and_display_saved_values_without_nan(): void
    {
        $indicators = collect([
            (object) ['indicator_code' => 'SF-0a', 'label' => 'Number of buildings with safely managed sanitation'],
            (object) ['indicator_code' => 'SF-0b', 'label' => 'Number of households with safely managed sanitation'],
            (object) ['indicator_code' => 'SF-0c', 'label' => 'Population with safely managed sanitation'],
        ]);
        $html = view('cwis.cwis-dashboard.chart-layout.safety-count-cards', [
            'safetyIndicators' => $indicators, 'data' => collect(['SF-0a' => '13643.000', 'SF-0b' => 'NaN']),
        ])->render();
        $this->assertStringContainsString('flex: 0 0 100%', $html);
        $this->assertStringContainsString('>13,643</div>', $html);
        $this->assertStringNotContainsString('13643.000', $html);
        $this->assertStringNotContainsString('NaN', $html);
        $this->assertSame(2, substr_count($html, '>No data</div>'));
        $this->assertSame(3, substr_count($html, 'data-indicator-code='));
    }
}
