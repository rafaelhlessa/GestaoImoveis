<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Ativar Conta</title>
</head>
<body>
    <h1>Olá, {{ $user->name }}</h1>
    <p>Por favor, clique no link abaixo para terminar o cadastro e ativar sua conta:</p>
    <a href="{{ $activationLink }}">Ativar Conta</a>
</body>
</html>
