<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relatório de Emails — ComprovaBet</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; }

        .header {
            background: #20210e;
            color: #fff;
            padding: 18px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { font-size: 16px; font-weight: 700; }
        .header .subtitle { font-size: 10px; color: rgba(255,255,255,.7); margin-top: 2px; }
        .header .date { font-size: 10px; color: rgba(255,255,255,.7); text-align: right; }

        .summary {
            padding: 14px 24px;
            background: #f4f5e6;
            border-bottom: 1px solid #ddd;
            display: flex;
            gap: 32px;
        }
        .summary .item { display: inline-block; }
        .summary .label { font-size: 9px; text-transform: uppercase; letter-spacing: .5px; color: #666; }
        .summary .value { font-size: 14px; font-weight: 700; color: #20210e; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        thead th {
            background: #f0f0f0;
            padding: 8px 10px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #555;
            border-bottom: 2px solid #ddd;
        }
        tbody td {
            padding: 7px 10px;
            border-bottom: 1px solid #eee;
            font-size: 10px;
            word-break: break-all;
        }
        tbody tr:nth-child(even) { background: #fafafa; }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: 700;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-failed { background: #f8d7da; color: #721c24; }

        .footer {
            margin-top: 16px;
            padding: 10px 24px;
            font-size: 9px;
            color: #999;
            text-align: center;
            border-top: 1px solid #eee;
        }

        .empty {
            padding: 40px;
            text-align: center;
            color: #999;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>ComprovaBet — Relatório de Emails Enviados</h1>
            <div class="subtitle">Relatórios Anuais de Ganhos</div>
        </div>
        <div class="date">
            Gerado em: {{ $summary['generated_at'] }}
        </div>
    </div>

    <div class="summary">
        <div class="item">
            <div class="label">Total de Envios</div>
            <div class="value">{{ $summary['total'] }}</div>
        </div>
        <div class="item">
            <div class="label">Sucesso</div>
            <div class="value" style="color: #155724;">{{ $summary['success'] }}</div>
        </div>
        <div class="item">
            <div class="label">Falhas</div>
            <div class="value" style="color: #721c24;">{{ $summary['failed'] }}</div>
        </div>
    </div>

    @if(count($entries) > 0)
    <table>
        <thead>
            <tr>
                <th>Data/Hora</th>
                <th>Player ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Ano</th>
                <th>Status</th>
                <th>Message-ID</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entries as $entry)
            <tr>
                <td>{{ $entry['timestamp'] ? \Carbon\Carbon::parse($entry['timestamp'])->format('d/m/Y H:i:s') : '—' }}</td>
                <td>{{ $entry['player_id'] }}</td>
                <td>{{ $entry['username'] ?? '—' }}</td>
                <td>{{ $entry['email'] ?? '—' }}</td>
                <td>{{ $entry['year'] ?? '—' }}</td>
                <td>
                    @if($entry['status'] === 'success')
                        <span class="badge badge-success">Enviado</span>
                    @else
                        <span class="badge badge-failed">Falha</span>
                    @endif
                </td>
                <td style="font-size: 8px;">{{ $entry['message_id'] ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="empty">
        Nenhum registro de envio encontrado no log.
    </div>
    @endif

    <div class="footer">
        ComprovaBet — Backoffice Betaki &bull; Relatório gerado automaticamente
    </div>
</body>
</html>
