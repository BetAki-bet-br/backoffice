<?php

namespace Tests\Feature;

use App\Mail\AnnualEarningsReportMail;
use Tests\TestCase;

class AnnualEarningsReportMailTest extends TestCase
{
    private array $playerData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->playerData = [
            'currency' => 'BRL',
            'player_id' => '10310001',
            'bets' => 183.70,
            'username' => '00000427063',
            'bet_count' => 271,
            'wins' => 83.67,
            'redeemed_bonuses' => 0.0,
            'net_income' => 100.03,
            'deposits' => 100.0,
            'withdrawals' => 0.0,
            'balance_start_real' => 0.0,
            'balance_end_real' => 0.33,
            'balance_start_bonus' => 0.0,
            'balance_end_bonus' => 0.0,
        ];
    }

    public function test_email_contains_player_username(): void
    {
        $mail = new AnnualEarningsReportMail($this->playerData, 2025);

        $mail->assertSeeInHtml('00000427063');
    }

    public function test_email_contains_year(): void
    {
        $mail = new AnnualEarningsReportMail($this->playerData, 2025);

        $mail->assertSeeInHtml('2025');
    }

    public function test_email_contains_formatted_currency_values(): void
    {
        $mail = new AnnualEarningsReportMail($this->playerData, 2025);

        $mail->assertSeeInHtml('R$ 183,70');
        $mail->assertSeeInHtml('R$ 83,67');
        $mail->assertSeeInHtml('R$ 100,03');
    }

    public function test_email_subject_includes_year(): void
    {
        $mail = new AnnualEarningsReportMail($this->playerData, 2025);

        $this->assertEquals('Relatório Anual de Ganhos 2025 - Betaki', $mail->envelope()->subject);
    }

    public function test_email_has_correct_view(): void
    {
        $mail = new AnnualEarningsReportMail($this->playerData, 2025);

        $this->assertEquals('emails.annual-earnings-report', $mail->content()->view);
    }

    public function test_email_shows_product_income_section_when_provided(): void
    {
        $productIncomes = [
            ['product_type' => 'Casino', 'income' => 100.03, 'balance_end' => 0.33],
            ['product_type' => 'Sportsbook', 'income' => 5893.71, 'balance_end' => 0.37],
        ];

        $mail = new AnnualEarningsReportMail($this->playerData, 2025, $productIncomes);

        $mail->assertSeeInHtml('Detalhamento por Tipo de Produto');
        $mail->assertSeeInHtml('Casino');
        $mail->assertSeeInHtml('Sportsbook');
        $mail->assertSeeInHtml('R$ 100,03');
        $mail->assertSeeInHtml('R$ 5.893,71');
    }

    public function test_email_hides_product_income_section_when_empty(): void
    {
        $mail = new AnnualEarningsReportMail($this->playerData, 2025, []);

        $mail->assertDontSeeInHtml('Detalhamento por Tipo de Produto');
    }
}
