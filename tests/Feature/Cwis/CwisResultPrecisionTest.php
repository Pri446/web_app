<?php

namespace Tests\Feature\Cwis;

use App\Models\Cwis\cwis_mne;
use App\Support\CwisValueFormatter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CwisResultPrecisionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_formatter_handles_numeric_and_qualitative_results(): void
    {
        foreach (['4.3478260869565217' => '4.348', '0.79051636480951787214' => '0.791',
            '25.0000000000000' => '25.000', '0' => '0.000', '-0.00001' => '0.000',
            '1e-4' => '0.000', 'Yes' => 'Yes', 'No' => 'No', 'NaN' => 'NaN',
            'Not applicable' => 'Not applicable'] as $input => $expected) {
            $this->assertSame($expected, CwisValueFormatter::format((string) $input));
        }
        $this->assertNull(CwisValueFormatter::format(null));
        $model = new cwis_mne();
        $model->setRawAttributes(['data_value' => '4.3478260869565217'], true);
        $this->assertSame('4.348', $model->data_value);
        $this->assertSame('4.3478260869565217', $model->getRawOriginal('data_value'));
    }

    public function test_database_rounds_insert_and_update_for_both_outcomes(): void
    {
        if (!DB::selectOne("SELECT EXISTS(SELECT 1 FROM pg_trigger WHERE tgname='round_cwis_saved_results' AND tgrelid='cwis.data_cwis'::regclass) AS installed")->installed) {
            require_once database_path('migrations/cwis/equity/2026_10_27_000003_limit_cwis_result_decimal_places.php');
            (new \LimitCwisResultDecimalPlaces())->up();
        }
        foreach (['EQ-3' => 'equity', 'SF-1f' => 'safety'] as $code => $outcome) {
            $id = DB::table('cwis.data_cwis')->insertGetId([
                'year' => 2098, 'indicator_code' => $code, 'outcome' => $outcome,
                'label' => 'Transactional precision test', 'data_value' => '4.3478260869565217',
            ]);
            $this->assertSame('4.348', DB::table('cwis.data_cwis')->where('id',$id)->value('data_value'));
            DB::table('cwis.data_cwis')->where('id',$id)->update(['data_value' => '8.333333333333333']);
            $this->assertSame('8.333', DB::table('cwis.data_cwis')->where('id',$id)->value('data_value'));
            DB::table('cwis.data_cwis')->where('id',$id)->update(['data_value' => 'Not applicable']);
            $this->assertSame('Not applicable', DB::table('cwis.data_cwis')->where('id',$id)->value('data_value'));
        }
    }
}
