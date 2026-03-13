<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório Anual de Ganhos {{ $year }}</title>
</head>

<body style="margin: 0; padding: 0; background-color: #f4f4f7; font-family: Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
        style="background-color: #f4f4f7; padding: 24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0"
                    style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                    {{-- Header --}}
                    <tr>
                        <td style="background-color: #869502; padding: 32px 40px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 700;">Betaki</h1>
                            <p style="color: #ffffff; margin: 8px 0 0; font-size: 14px;">Relatório Anual de Ganhos —
                                {{ $year }}</p>
                        </td>
                    </tr>

                    {{-- Greeting --}}
                    <tr>
                        <td style="padding: 32px 40px 16px;">
                            <p style="margin: 0; font-size: 16px; color: #333333;">Olá,
                                <strong>{{ $username }}</strong>,
                            </p>
                            <p style="margin: 12px 0 0; font-size: 14px; color: #555555; line-height: 1.6;">
                                Segue abaixo o resumo das suas movimentações financeiras referentes ao ano de
                                <strong>{{ $year }}</strong>,
                                conforme exigido pela regulamentação vigente para casas de apostas.
                            </p>
                        </td>
                    </tr>

                    {{-- Data Table --}}
                    <tr>
                        <td style="padding: 16px 40px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                style="border: 1px solid #e0e0e0; border-radius: 6px; overflow: hidden;">
                                <tr style="background-color: #f8f8fc;">
                                    <td
                                        style="padding: 12px 16px; font-size: 13px; font-weight: 700; color: #869502; border-bottom: 1px solid #e0e0e0;">
                                        Descrição</td>
                                    <td
                                        style="padding: 12px 16px; font-size: 13px; font-weight: 700; color: #869502; border-bottom: 1px solid #e0e0e0; text-align: right;">
                                        Valor</td>
                                </tr>
                                @php
                                    $rows = [
                                        ['Total de Apostas', $bets],
                                        ['Quantidade de Apostas', $betCount],
                                        ['Total de Ganhos', $wins],
                                        ['Bônus Resgatados', $redeemedBonuses],
                                        ['Depósitos', $deposits],
                                        ['Saques', $withdrawals],
                                        ['Saldo Real (Início)', $balanceStartReal],
                                        ['Saldo Real (Final)', $balanceEndReal],
                                        ['Saldo Bônus (Início)', $balanceStartBonus],
                                        ['Saldo Bônus (Final)', $balanceEndBonus],
                                    ];
                                @endphp
                                @foreach ($rows as $index => $row)
                                    <tr style="background-color: {{ $index % 2 === 0 ? '#ffffff' : '#fafafc' }};">
                                        <td
                                            style="padding: 10px 16px; font-size: 13px; color: #555555; border-bottom: 1px solid #f0f0f0;">
                                            {{ $row[0] }}</td>
                                        <td
                                            style="padding: 10px 16px; font-size: 13px; color: #333333; text-align: right; border-bottom: 1px solid #f0f0f0;">
                                            {{ $row[1] }}</td>
                                    </tr>
                                @endforeach
                                {{-- Net Income Highlight --}}
                                <tr style="background-color: #869502;">
                                    <td style="padding: 14px 16px; font-size: 14px; font-weight: 700; color: #ffffff;">
                                        Resultado Líquido</td>
                                    <td
                                        style="padding: 14px 16px; font-size: 14px; font-weight: 700; color: #ffffff; text-align: right;">
                                        {{ $netIncome }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding: 24px 40px; background-color: #f8f8fc; border-top: 1px solid #e0e0e0;">
                            <p style="margin: 0; font-size: 12px; color: #888888; line-height: 1.5;">
                                Este relatório é gerado automaticamente com base nos dados fornecidos pelo nosso
                                agregador de jogos
                                e destina-se ao cumprimento das obrigações regulatórias. Caso identifique alguma
                                divergência,
                                entre em contato com nosso suporte.
                            </p>
                            <p style="margin: 12px 0 0; font-size: 12px; color: #aaaaaa;">
                                &copy; {{ $year }} Betaki. Todos os direitos reservados.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
