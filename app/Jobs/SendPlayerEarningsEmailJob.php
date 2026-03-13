<?php

namespace App\Jobs;

use App\Mail\AnnualEarningsReportMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPlayerEarningsEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly array $playerData,
        public readonly int $year,
    ) {
        $this->onQueue('emails');
    }

    public function handle(): void
    {
        $playerId = $this->playerData['player_id'];
        $username = $this->playerData['username'];
        $email = $this->playerData['email'];
        $cpf = $this->playerData['cpf'] ?? 'N/A';
        $netIncome = $this->playerData['net_income'] ?? 'N/A';
        $currency = $this->playerData['currency'] ?? 'N/A';
        $attempt = $this->attempts();
        $jobId = $this->job?->getJobId() ?? 'sync';
        $mailer = config('mail.default');
        $fromAddress = config('mail.from.address');
        $fromName = config('mail.from.name');

        Log::channel('earnings')->info("[EARNINGS][JOB:{$jobId}] Iniciando envio de email — Tentativa: {$attempt}/{$this->tries}, Player ID: {$playerId}, Username: {$username}, CPF: {$cpf}, Email destino: {$email}, Ano: {$this->year}, Net Income: {$netIncome} {$currency}, Mailer: {$mailer}, From: {$fromName} <{$fromAddress}>");

        $mailable = new AnnualEarningsReportMail($this->playerData, $this->year);

        Mail::to($email)->send($mailable);

        $messageId = method_exists($mailable, 'getSymfonySentMessage') && $mailable->getSymfonySentMessage()
            ? $mailable->getSymfonySentMessage()->getMessageId()
            : 'N/A';

        Log::channel('earnings')->info("[EARNINGS][JOB:{$jobId}] SUCESSO — Email enviado com sucesso para Player ID: {$playerId}, Username: {$username}, Email: {$email}, Ano: {$this->year}, Message-ID: {$messageId}, Tentativa: {$attempt}/{$this->tries}");
    }

    public function failed(\Throwable $exception): void
    {
        $playerId = $this->playerData['player_id'] ?? 'unknown';
        $username = $this->playerData['username'] ?? 'unknown';
        $email = $this->playerData['email'] ?? 'unknown';
        $cpf = $this->playerData['cpf'] ?? 'unknown';
        $attempt = $this->attempts();
        $jobId = $this->job?->getJobId() ?? 'unknown';
        $mailer = config('mail.default');

        Log::channel('earnings')->error("[EARNINGS][JOB:{$jobId}] FALHA DEFINITIVA — Todas as {$this->tries} tentativas esgotadas. Player ID: {$playerId}, Username: {$username}, CPF: {$cpf}, Email: {$email}, Ano: {$this->year}, Mailer: {$mailer}, Tentativa final: {$attempt}/{$this->tries}, Exceção: {$exception->getMessage()}, Trace: ".$exception->getTraceAsString());
    }
}
