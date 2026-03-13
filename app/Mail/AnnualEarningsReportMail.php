<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AnnualEarningsReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly array $playerData,
        public readonly int $year,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Relatório Anual de Ganhos {$this->year} - Betaki",
        );
    }

    public function content(): Content
    {
        $currency = $this->playerData['currency'];
        $symbol = $currency === 'BRL' ? 'R$' : $currency;

        $format = fn (float $value) => $symbol . ' ' . number_format($value, 2, ',', '.');

        return new Content(
            view: 'emails.annual-earnings-report',
            with: [
                'username' => $this->playerData['username'],
                'year' => $this->year,
                'currency' => $currency,
                'bets' => $format($this->playerData['bets']),
                'betCount' => number_format($this->playerData['bet_count'], 0, ',', '.'),
                'wins' => $format($this->playerData['wins']),
                'redeemedBonuses' => $format($this->playerData['redeemed_bonuses']),
                'netIncome' => $format($this->playerData['net_income']),
                'deposits' => $format($this->playerData['deposits']),
                'withdrawals' => $format($this->playerData['withdrawals']),
                'balanceStartReal' => $format($this->playerData['balance_start_real']),
                'balanceEndReal' => $format($this->playerData['balance_end_real']),
                'balanceStartBonus' => $format($this->playerData['balance_start_bonus']),
                'balanceEndBonus' => $format($this->playerData['balance_end_bonus']),
            ],
        );
    }
}
