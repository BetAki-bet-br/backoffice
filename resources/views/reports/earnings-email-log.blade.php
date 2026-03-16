<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <title>Sumário de Logs - Relatórios de Ganhos ({{ $summary['year'] }})</title>
    <style>
      body {
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f4f7f6;
        color: #333;
        margin: 0;
        padding: 20px;
      }
      .container {
        max-width: 900px;
        margin: 0 auto;
        background: #fff;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      }
      h1, h2 {
        color: #2c3e50;
      }
      h1 { font-size: 1.5em; }
      h2 { font-size: 1.15em; margin-top: 30px; }
      .subtitle {
        color: #666;
        font-size: 0.95em;
        margin-bottom: 20px;
      }
      .summary-cards {
        display: flex;
        gap: 20px;
        margin-bottom: 30px;
      }
      .card {
        flex: 1;
        padding: 20px;
        border-radius: 8px;
        color: white;
        text-align: center;
      }
      .card.success { background-color: #27ae60; }
      .card.warning { background-color: #e67e22; }
      .card h3 { margin: 0; font-size: 2em; }
      .card p { margin: 5px 0 0; font-size: 1.1em; }
      table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 30px;
      }
      th, td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
      }
      th {
        background-color: #ecf0f1;
        color: #333;
      }
      tr:hover { background-color: #f9f9f9; }
      .status-success { color: #27ae60; font-weight: bold; }
      .status-error { color: #c0392b; font-weight: bold; }
      .income-positive { color: #27ae60; }
      .income-negative { color: #c0392b; }
      .empty { padding: 20px; text-align: center; color: #999; }
      .footer {
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #eee;
        font-size: 0.8em;
        color: #999;
        text-align: center;
      }
    </style>
  </head>
  <body>
    <div class="container">
      <h1>Sumário de Disparos de Email (Betaki)</h1>
      <p class="subtitle">
        Resumo da extração de logs referente ao ano-base de {{ $summary['year'] }}.
        Registros duplicados do mesmo usuário nas requisições foram agrupados para melhor visualização.
      </p>

      <div class="summary-cards">
        <div class="card success">
          <h3>{{ $summary['success'] }}</h3>
          <p>Emails Enviados com Sucesso</p>
        </div>
        <div class="card warning">
          <h3>{{ $summary['problems'] }}</h3>
          <p>Jogadores Não Encontrados</p>
        </div>
      </div>

      <h2>✅ Emails Despachados com Sucesso</h2>
      @if(count($successEntries) > 0)
      <table>
        <thead>
          <tr>
            <th>Player ID</th>
            <th>Email Destino</th>
            <th>Net Income (BRL)</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @foreach($successEntries as $entry)
          <tr>
            <td>{{ $entry['player_id'] }}</td>
            <td>{{ $entry['email'] ?? '—' }}</td>
            <td class="{{ ($entry['net_income'] !== null && (float) $entry['net_income'] >= 0) ? 'income-positive' : 'income-negative' }}">
              {{ $entry['net_income'] ?? '—' }}
            </td>
            <td class="status-success">Enviado</td>
          </tr>
          @endforeach
        </tbody>
      </table>
      @else
      <div class="empty">Nenhum email enviado com sucesso encontrado no log.</div>
      @endif

      @if(count($problemEntries) > 0)
      <h2>⚠️ Jogadores Não Encontrados na Planilha</h2>
      <table>
        <thead>
          <tr>
            <th>Player ID</th>
            <th>Email Buscado</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @foreach($problemEntries as $entry)
          <tr>
            <td>{{ $entry['player_id'] }}</td>
            <td>{{ $entry['email'] ?? '—' }}</td>
            <td class="status-error">{{ $entry['reason'] ?? 'Falha - Não Encontrado' }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
      @endif

      <div class="footer">
        ComprovaBet — Backoffice Betaki &bull; Gerado em {{ $summary['generated_at'] }}
      </div>
    </div>
  </body>
</html>
