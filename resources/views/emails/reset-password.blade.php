<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinição de Senha - Propriedades na Mão</title>
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
            <img src="/storage/app/public/logo-sem fundo.png" alt="Propriedades na Mão" class="logo" onerror="this.onerror=null; this.src=''; this.alt='Propriedades na Mão';">
            <h1>Redefinição de Senha</h1>
        </div>
        
        <div class="content">
            <p>Olá, <strong>{{ $name }}</strong>!</p>
            
            <p>Recebemos uma solicitação para redefinir a senha da sua conta no <strong>Propriedades na Mão</strong>. Para criar uma nova senha, clique no botão abaixo:</p>
            
            <div class="button-container">
                <a href="{{ $resetLink }}" class="button">REDEFINIR MINHA SENHA</a>
            </div>
            
            <p>Ou, se preferir, copie e cole o link abaixo no seu navegador:</p>
            <div class="link-container">
                {{ $resetLink }}
            </div>
            
            <div class="info-box">
                <p><strong>Atenção:</strong> Este link é válido por 60 minutos. Após esse período, você precisará solicitar um novo link de redefinição de senha.</p>
            </div>
            
            <p>Se você não solicitou esta redefinição de senha, por favor ignore este email ou entre em contato com nossa equipe de suporte caso tenha alguma dúvida.</p>
            
            <p>Atenciosamente,<br>Equipe Propriedades na Mão</p>
            
            <div class="footer">
                <p>© {{ date('Y') }} Propriedades na Mão. Todos os direitos reservados.</p>
            </div>
        </div>
    </div>
</body>
</html>