<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Models\Domain\Telegram\TelegramBot;
use App\Models\Domain\Telegram\BotStatistic;
use Illuminate\Support\Collection;

/**
 * Serviço de analytics e estatísticas de bots
 */
class BotStatisticService
{
    /**
     * Obtém resumo estatístico de um bot
     */
    public function getSummary(TelegramBot $bot, int $days = 30): array
    {
        $statistics = $bot->statistics()
            ->where('date', '>=', now()->subDays($days))
            ->get();

        return [
            'period_days' => $days,
            'total_messages_sent' => $statistics->sum('messages_sent'),
            'total_messages_received' => $statistics->sum('messages_received'),
            'total_messages' => $statistics->sum('messages_sent') + $statistics->sum('messages_received'),
            'total_users_new' => $statistics->sum('users_new'),
            'total_validations_success' => $statistics->sum('validations_success'),
            'total_validations_failed' => $statistics->sum('validations_failed'),
            'validation_success_rate' => $this->calculateSuccessRate($statistics),
            'daily_average_messages' => $this->calculateDailyAverage($statistics, 'total_messages'),
            'daily_average_validations' => $this->calculateDailyAverage($statistics, 'total_validations'),
        ];
    }

    /**
     * Obtém estatísticas por período (ex: últimos 7 dias)
     */
    public function getChartData(TelegramBot $bot, int $days = 30): array
    {
        $statistics = $bot->statistics()
            ->where('date', '>=', now()->subDays($days))
            ->orderBy('date')
            ->get();

        return [
            'dates' => $statistics->pluck('date')->map(fn($date) => $date->format('Y-m-d'))->toArray(),
            'messages_sent' => $statistics->pluck('messages_sent')->toArray(),
            'messages_received' => $statistics->pluck('messages_received')->toArray(),
            'validations_success' => $statistics->pluck('validations_success')->toArray(),
            'validations_failed' => $statistics->pluck('validations_failed')->toArray(),
            'users_new' => $statistics->pluck('users_new')->toArray(),
        ];
    }

    /**
     * Obtém usuários validados
     */
    public function getValidatedUsers(TelegramBot $bot, int $limit = 50): Collection
    {
        return $bot->botUsers()
            ->where('status', 'validated')
            ->orderByDesc('validated_at')
            ->limit($limit)
            ->get(['id', 'telegram_user_id', 'first_name', 'email', 'validated_at']);
    }

    /**
     * Obtém usuários que falharam
     */
    public function getFailedUsers(TelegramBot $bot, int $limit = 50): Collection
    {
        return $bot->botUsers()
            ->where('status', 'failed')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get(['id', 'telegram_user_id', 'first_name', 'email', 'metadata']);
    }

    /**
     * Obtém logs de mensagens
     */
    public function getMessageLogs(TelegramBot $bot, int $limit = 100): Collection
    {
        return $bot->messages()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->with('botUser')
            ->get(['id', 'bot_user_id', 'direction', 'type', 'content', 'status', 'created_at']);
    }

    /**
     * Calcula taxa de sucesso de validação
     */
    protected function calculateSuccessRate(Collection $statistics): float
    {
        $totalSuccess = $statistics->sum('validations_success');
        $totalFailed = $statistics->sum('validations_failed');
        $total = $totalSuccess + $totalFailed;

        if ($total === 0) {
            return 0;
        }

        return round(($totalSuccess / $total) * 100, 2);
    }

    /**
     * Calcula média diária
     */
    protected function calculateDailyAverage(Collection $statistics, string $type): float
    {
        if ($statistics->isEmpty()) {
            return 0;
        }

        $total = match ($type) {
            'total_messages' => $statistics->sum('messages_sent') + $statistics->sum('messages_received'),
            'total_validations' => $statistics->sum('validations_success') + $statistics->sum('validations_failed'),
            default => 0,
        };

        return round($total / $statistics->count(), 2);
    }

    /**
     * Exporta dados em CSV
     */
    public function exportToCSV(TelegramBot $bot, int $days = 30): string
    {
        $statistics = $bot->statistics()
            ->where('date', '>=', now()->subDays($days))
            ->orderBy('date')
            ->get();

        $csv = "Data,Mensagens Enviadas,Mensagens Recebidas,Usuários Novos,Validações Sucesso,Validações Falha\n";

        foreach ($statistics as $stat) {
            $csv .= implode(',', [
                $stat->date->format('Y-m-d'),
                $stat->messages_sent,
                $stat->messages_received,
                $stat->users_new,
                $stat->validations_success,
                $stat->validations_failed,
            ]) . "\n";
        }

        return $csv;
    }
}
