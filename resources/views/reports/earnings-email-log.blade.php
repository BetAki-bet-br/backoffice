<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sumário de Logs - Relatórios de Ganhos ({{ $summary['year'] }})</title>
    <style>
      body {
        font-family: DejaVu Sans, sans-serif;
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
        border: 1px solid #ddd;
      }
      h1,
      h2 {
        color: #2c3e50;
      }
      .summary-cards {
        width: 100%;
        border-spacing: 20px 0;
        margin-left: -20px;
        margin-bottom: 30px;
      }
      .summary-cards td {
        width: 50%;
        padding: 20px;
        color: white;
        text-align: center;
        border-bottom: none;
      }
      .summary-cards td.success {
        background-color: #27ae60;
      }
      .summary-cards td.warning {
        background-color: #e67e22;
      }
      .summary-cards h3 {
        margin: 0;
        font-size: 2em;
      }
      .summary-cards p {
        margin: 5px 0 0;
        font-size: 1.1em;
      }
      table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 30px;
      }
      th,
      td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
      }
      th {
        background-color: #ecf0f1;
        color: #333;
      }
      .status-success {
        color: #27ae60;
        font-weight: bold;
      }
      .status-error {
        color: #c0392b;
        font-weight: bold;
      }
      .income-positive {
        color: #27ae60;
      }
      .income-negative {
        color: #c0392b;
      }
      .empty {
        padding: 20px;
        text-align: center;
        color: #999;
      }
    </style>
  </head>
  <body>
    <div class="container">
      <h1>Sumário de Disparos de Email (Betaki)</h1>
      <p>
        Resumo da extração de logs referente ao ano-base de {{ $summary['year'] }}.
        Registros duplicados do mesmo usuário nas requisições foram agrupados para melhor visualização.
      </p>
      <table class="summary-cards">
        <tr>
          <td class="success">
            <h3>{{ $summary['success'] }}</h3>
            <p>Emails Enviados com Sucesso</p>
          </td>
          <td class="warning">
            <h3>{{ $summary['problems'] }}</h3>
            <p>Jogadores Não Encontrados</p>
          </td>
        </tr>
      </table>
      <h2>Emails Despachados com Sucesso</h2>
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
      <h2>Jogadores Não Encontrados na Planilha</h2>
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
    </div>
  </body>
</html>
