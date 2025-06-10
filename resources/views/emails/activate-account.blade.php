<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ative sua conta - Propriedades na Mão</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            line-height: 1.6;
            color: #e4e6eb;
            background-color: #171b26;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 0;
        }
        
        .header {
            background-color: #1a1f2e;
            padding: 25px 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        
        .logo {
            max-width: 150px;
            margin-bottom: 10px;
        }
        
        .content {
            background-color: #171b26;
            padding: 40px 30px;
            border-radius: 0 0 10px 10px;
            border: 1px solid #2e3546;
            border-top: none;
        }
        
        h1 {
            color: #ffffff;
            font-size: 28px;
            margin: 0 0 25px 0;
            font-weight: 500;
        }
        
        p {
            margin: 0 0 20px 0;
            font-size: 16px;
            line-height: 1.7;
            color:#e4e6eb;
        }
        
        .button-container {
            text-align: center;
            margin: 35px 0;
        }
        
        .button {
            background-color: #6372e5;
            color: #ffffff;
            padding: 16px 35px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .button:hover {
            background-color: #5361d4;
        }
        
        .link-container {
            background-color: #1a1f2e;
            padding: 15px;
            border-radius: 5px;
            margin: 25px 0;
            word-break: break-all;
            font-size: 14px;
            color: #c9ccd1;
        }
        
        .footer {
            font-size: 13px;
            color: #9ba1b0;
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #2e3546;
        }
        
        .info-box {
            background-color: #1a1f2e;
            border-left: 4px solid #6372e5;
            padding: 15px 20px;
            margin: 25px 0;
            border-radius: 0 5px 5px 0;
        }
        
        .info-box p {
            margin: 0;
            font-size: 14px;
        }
        
        @media only screen and (max-width: 620px) {
            .container {
                width: 100%;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ $message->embed(public_path('storage/logo-sem fundo.png')) }}" alt="Propriedades na Mão" class="logo" onerror="this.onerror=null; this.src=''; this.alt='Propriedades na Mão';">
            <h1>Ative sua conta agora</h1>
        </div>
        
        <div class="content">
            <p>Olá, <strong>{{ $name }}</strong>!</p>
            
            <p>Obrigado por se cadastrar no <strong>Propriedades na Mão</strong>. Estamos muito felizes em ter você conosco! Para começar a usar todos os recursos da nossa plataforma de gestão de imóveis, precisamos que você ative sua conta.</p>
            
            <div class="button-container">
                <a href="{{ $activationLink }}" class="button" style="color: #ffffff;">ATIVAR MINHA CONTA</a>
            </div>
            
            <p>Ou, se preferir, copie e cole o link abaixo no seu navegador:</p>
            <div class="link-container">
                {{ $activationLink }}
            </div>
            
            <div class="info-box">
                <p><strong>Atenção:</strong> Este link é válido por 24 horas. Após esse período, você precisará solicitar um novo link de ativação.</p>
            </div>
            
            <p>Após a ativação, você terá acesso completo à nossa plataforma e poderá começar a gerenciar seus imóveis de forma eficiente e inteligente.</p>
            
            <p>Estamos à disposição para ajudá-lo(a) em qualquer dúvida que possa surgir.</p>
            
            <p>Atenciosamente,<br>Equipe Propriedades na Mão</p>
            
            <div class="footer">
                <p>© {{ date('Y') }} Propriedades na Mão. Todos os direitos reservados.</p>
                <p>Se você não solicitou esta conta, por favor ignore este email.</p>
            </div>
        </div>
    </div>
</body>
</html>