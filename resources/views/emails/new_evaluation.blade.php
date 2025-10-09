<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Nova avaliação disponível</title>
</head>
<body>
  <p>Olá,</p>
  <p>Uma nova avaliação do seu imóvel "{{ $property->nickname ?? ('#'.$property->id) }}" foi realizada por {{ $evaluation->appraiser }} em {{ optional($evaluation->created_at)->format('d/m/Y H:i') }}.</p>
  <p>Valor avaliado: <strong>R$ {{ number_format((float)$evaluation->valuation, 2, ',', '.') }}</strong></p>
  <p>Acesse o sistema para visualizar os detalhes e confirmar o recebimento da avaliação.</p>
  <p>Atenciosamente,<br/>Propriedades na Mão</p>
</body>
</html>
