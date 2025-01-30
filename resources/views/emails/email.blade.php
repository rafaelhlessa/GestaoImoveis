<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmação de E-mail</title>
</head>
<body style="margin: 0; padding: 0; background-color: #2D3748; font-family: Arial, sans-serif;">
    <table width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center">
                <table width="600px" style="background-color: #1A202C; padding: 20px; border-radius: 8px; text-align: center;">
                    
                    {{-- Logo Centralizado --}}
                    <tr>
                        <td style="padding-bottom: 20px; border-radius: 8px;"> 
                            <img src="{{ asset('storage/logo.png') }}" alt="Logo da Empresa" width="150">
                        </td>
                    </tr>

                    {{-- Mensagem de Agradecimento --}}
                    <tr>
                        <td>
                            <h2 style="color: #ffffff; margin-bottom: 10px;">Olá, {{ $user->name }}!</h2>
                            <p style="color: #CBD5E0; font-size: 16px;">
                                Agradecemos por se cadastrar! Confirme seu e-mail clicando no botão abaixo:
                            </p>
                        </td>
                    </tr>

                    {{-- Botão de Confirmação --}}
                    <tr>
                        <td style="padding: 20px;">
                            <a href="{{ $activationLink }}" 
                               style="background-color: #4A5568; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-size: 16px;">
                                Ativar Conta
                            </a>
                        </td>
                    </tr>

                    {{-- Rodapé --}}
                    <tr>
                        <td style="padding-top: 20px;">
                            <p style="color: #A0AEC0; font-size: 14px;">
                                Se você não criou esta conta, ignore este e-mail.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
