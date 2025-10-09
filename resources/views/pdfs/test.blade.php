<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Laudo de Avaliação de Imóvel</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }
        
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'DejaVu Sans', 'Arial', sans-serif; 
            font-size: 11px; 
            color: #2c3e50; 
            line-height: 1.6;
        }
        
        .header { 
            background: #667eea; 
            padding: 20px; 
            color: white;
            margin-bottom: 20px;
        }
        
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .header-logo { 
            width: 25%;
            vertical-align: middle;
        }
        
        .header-logo img { 
            max-width: 100px; 
            height: auto; 
            background: white; 
            padding: 8px; 
            border-radius: 4px; 
        }
        
        .header-info { 
            width: 75%;
            vertical-align: middle;
            text-align: right;
            padding-left: 15px;
        }
        
        .header-title { 
            font-size: 20px; 
            font-weight: bold; 
            margin-bottom: 8px; 
            text-transform: uppercase; 
        }
        
        .header-subtitle { 
            font-size: 11px; 
            line-height: 1.6; 
        }
        
        .card { 
            background: #ffffff; 
            border: 1px solid #e1e8ed; 
            border-radius: 6px; 
            padding: 15px; 
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        
        .card-header { 
            background: #667eea; 
            color: white; 
            padding: 10px 15px; 
            margin: -15px -15px 15px -15px; 
            border-radius: 6px 6px 0 0; 
            font-size: 13px; 
            font-weight: bold; 
            text-transform: uppercase; 
        }
        
        .info-table { 
            width: 100%; 
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        
        .info-table tr { 
            border-bottom: 1px solid #e1e8ed;
        }
        
        .info-table td { 
            padding: 10px; 
        }
        
        .info-table td:first-child { 
            font-weight: 600; 
            color: #667eea; 
            width: 35%; 
            background: #f0f3ff; 
        }
        
        .info-table td:last-child { 
            background: #ffffff; 
            border-left: 1px solid #e1e8ed;
        }
        
        .value-highlight { 
            background: #11998e; 
            color: white; 
            padding: 20px; 
            border-radius: 6px; 
            text-align: center; 
            margin: 15px 0; 
            page-break-inside: avoid;
        }
        
        .value-label { 
            font-size: 11px; 
            text-transform: uppercase; 
            margin-bottom: 5px; 
        }
        
        .value-amount { 
            font-size: 28px; 
            font-weight: bold; 
        }
        
        .observation-box { 
            background: #fff8e1; 
            border-left: 4px solid #ffc107; 
            padding: 12px; 
            color: #856404; 
            font-style: italic; 
        }
        
        .feature-label { 
            font-weight: 600; 
            color: #667eea; 
            font-size: 10px; 
            text-transform: uppercase; 
            margin: 12px 0 5px 0;
            padding: 5px 0;
        }
        
        .mb-10 {
            margin-bottom: 10px;
        }
        
        .footer { 
            border-top: 2px solid #667eea; 
            padding: 15px 0 5px 0; 
            margin-top: 20px;
            font-size: 9px; 
            color: #6c757d; 
            text-align: center;
        }
        
        .signature-box { 
            padding: 10px 0; 
            margin: 15px 0 10px 0; 
            text-align: center; 
        }
        
        .signature-line { 
            width: 250px; 
            margin: 0 auto 5px auto; 
            border-bottom: 2px solid #2c3e50; 
            height: 35px; 
        }
        
        .appraiser-info { 
            font-weight: bold; 
            color: #2c3e50; 
            font-size: 10px; 
            margin-bottom: 3px; 
        }
        
        .system-info { 
            color: #adb5bd; 
            margin-top: 8px;
        }
        
        .badge { 
            display: inline-block; 
            padding: 3px 8px; 
            background: #667eea; 
            color: white; 
            border-radius: 10px; 
            font-size: 9px; 
            font-weight: 600; 
            text-transform: uppercase; 
        }
        
        strong {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-logo">
                    @if(($header['logo_url'] ?? null))
                        {{-- Spatie/Browsershot aceita caminho absoluto de arquivo (public_path) ou URL completa --}}
                        <img src="{{ $header['logo_url'] }}" alt="Logo" />
                    @else
                        <img src="{{ asset('logo.png') }}" alt="Logo" />
                    @endif
                </td>
                <td class="header-info">
                    <div class="header-title">{{ $header['title'] ?? 'Laudo de Avaliação' }}</div>
                    <div class="header-subtitle">
                        {{ $header['appraiser']['name'] ?? '' }}
                        @if(($header['appraiser']['registry'] ?? null))<br>Registro Profissional: {{ $header['appraiser']['registry'] }}@endif
                        @if(($header['appraiser']['phone'] ?? null))<br>Tel: {{ $header['appraiser']['phone'] }}@endif
                        @if(($header['appraiser']['email'] ?? null))<br>Email: {{ $header['appraiser']['email'] }}@endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="card">
        <div class="card-header">Dados da Propriedade</div>
        <table class="info-table">
            <tr><td>Identificação</td><td><strong>{{ $property->nickname ?? $property->id }}</strong></td></tr>
            <tr><td>Localização</td><td>{{ $property->city ?? '' }}{{ $property->state ? ' / '.$property->state : '' }}</td></tr>
            @if($property->district)<tr><td>Bairro</td><td>{{ $property->district }}</td></tr>@endif
            @if($property->address ?? null)<tr><td>Endereço</td><td>{{ $property->address }}</td></tr>@endif
        </table>
    </div>

    <div class="value-highlight">
        <div class="value-label">Valor Avaliado</div>
        <div class="value-amount">R$ {{ number_format((float)$evaluation->valuation, 2, ',', '.') }}</div>
    </div>

    <div class="card">
        <div class="card-header">Informações da Avaliação</div>
        <table class="info-table">
            <tr><td>Avaliador Responsável</td><td>{{ $evaluation->appraiser }}</td></tr>
            <tr><td>Data da Avaliação</td><td>{{ optional($evaluation->created_at)->format('d/m/Y H:i') }}</td></tr>
            @if($evaluation->property_type)
            <tr><td>Tipo de Propriedade</td><td>
                @if($evaluation->property_type === 'rural')
                    Rural
                @elseif($evaluation->property_type === 'urbana')
                    {{ ($evaluation->urban_subtype === 'residencial') ? 'Residencial' : 'Comercial' }}
                @else
                    {{ ucfirst($evaluation->property_type) }}
                @endif
            </td></tr>
            @endif
            @if($evaluation->property_condition)
            <tr><td>Condição do Imóvel</td><td>{{ $evaluation->property_condition_label ?? ucfirst($evaluation->property_condition) }}</td></tr>
            @endif
            @if($evaluation->method ?? null)
            <tr><td>Metodologia Aplicada</td><td><span class="badge">{{ $evaluation->method }}</span></td></tr>
            @endif
            @if($evaluation->comments)
            <tr><td>Comentários</td><td>{{ $evaluation->comments }}</td></tr>
            @endif
        </table>
    </div>

    @php
        $d = is_array($details ?? null) ? $details : [];
        $used = [];
        $label = function($t){ return mb_convert_case(str_replace(['_', '-'], ' ', (string)$t), MB_CASE_TITLE, 'UTF-8'); };
        $fmt = function($v){ return is_bool($v) ? ($v ? 'Sim' : 'Não') : ((($v === null || $v === '') ? '-' : $v)); };
    @endphp

    @if(!empty($d) && $evaluation->property_type === 'urbana' && ($evaluation->urban_subtype === 'residencial'))
        @php $used = ['localizacao','terreno','construcao','distribuicao_interna','areas_externas','instalacoes','estado_conservacao']; @endphp
        <div class="card">
            <div class="card-header">Detalhes Residenciais</div>
            @if(isset($d['localizacao']))
            <div class="mb-10">
                <div class="feature-label">Localização</div>
                <table class="info-table">
                    @if(($d['localizacao']['proximidade_servicos'] ?? null))<tr><td>Proximidade</td><td>{{ $d['localizacao']['proximidade_servicos'] }}</td></tr>@endif
                    @if(($d['localizacao']['transporte'] ?? null))<tr><td>Transporte</td><td>{{ $d['localizacao']['transporte'] }}</td></tr>@endif
                    @if(($d['localizacao']['seguranca'] ?? null))<tr><td>Segurança</td><td>{{ $d['localizacao']['seguranca'] }}</td></tr>@endif
                </table>
            </div>
            @endif
            @if(isset($d['terreno']))
            <div class="mb-10">
                <div class="feature-label">Terreno</div>
                <table class="info-table">
                    @if(($d['terreno']['topografia'] ?? null))<tr><td>Topografia</td><td>{{ $d['terreno']['topografia'] }}</td></tr>@endif
                    @if(($d['terreno']['posicao'] ?? null))<tr><td>Posição</td><td>{{ $d['terreno']['posicao'] }}</td></tr>@endif
                </table>
            </div>
            @endif
            @if(isset($d['construcao']))
            <div class="mb-10">
                <div class="feature-label">Construção</div>
                <table class="info-table">
                    @foreach($d['construcao'] as $k => $v)
                        <tr><td>{{ $label($k) }}</td><td>{{ is_array($v) ? implode(', ', $v) : $fmt($v) }}</td></tr>
                    @endforeach
                </table>
            </div>
            @endif
            @if(isset($d['distribuicao_interna']))
            <div class="mb-10">
                <div class="feature-label">Distribuição Interna</div>
                <table class="info-table">
                    @foreach($d['distribuicao_interna'] as $k => $v)
                        <tr><td>{{ $label($k) }}</td><td>{{ is_array($v) ? implode(', ', $v) : $fmt($v) }}</td></tr>
                    @endforeach
                </table>
            </div>
            @endif
            @if(isset($d['areas_externas']))
            <div class="mb-10">
                <div class="feature-label">Áreas Externas</div>
                <table class="info-table">
                    @foreach($d['areas_externas'] as $k => $v)
                        <tr><td>{{ $label($k) }}</td><td>{{ $fmt($v) }}</td></tr>
                    @endforeach
                </table>
            </div>
            @endif
            @if(isset($d['instalacoes']))
            <div class="mb-10">
                <div class="feature-label">Instalações</div>
                <table class="info-table">
                    @foreach($d['instalacoes'] as $k => $v)
                        <tr><td>{{ $label($k) }}</td><td>{{ $fmt($v) }}</td></tr>
                    @endforeach
                </table>
            </div>
            @endif
            @if(isset($d['estado_conservacao']))
            <div class="mb-10">
                <div class="feature-label">Estado de Conservação</div>
                <table class="info-table">
                    @foreach($d['estado_conservacao'] as $k => $v)
                        <tr><td>{{ $label($k) }}</td><td>{{ is_array($v) ? implode(', ', $v) : $fmt($v) }}</td></tr>
                    @endforeach
                </table>
            </div>
            @endif
        </div>
        @php $remaining = array_diff_key($d, array_flip($used)); @endphp
        @if(!empty($remaining))
        <div class="card">
            <div class="card-header">Outros Detalhes</div>
            <table class="info-table">
                @foreach($remaining as $k => $v)
                    <tr><td>{{ $label($k) }}</td><td>@if(is_array($v)) {{ implode(', ', array_map(fn($i)=> is_array($i)? json_encode($i, JSON_UNESCAPED_UNICODE) : $i, $v)) }} @else {{ $fmt($v) }} @endif</td></tr>
                @endforeach
            </table>
        </div>
        @endif
    @elseif(!empty($d) && $evaluation->property_type === 'urbana' && ($evaluation->urban_subtype === 'comercial'))
        @php $used = ['layout','instalacoes']; @endphp
        <div class="card">
            <div class="card-header">Detalhes Comerciais</div>
            <table class="info-table">
                @if(($evaluation->floors ?? null) !== null)<tr><td>Pavimentos</td><td>{{ $evaluation->floors }}</td></tr>@endif
                @if(($evaluation->office_rooms ?? null) !== null)<tr><td>Salas</td><td>{{ $evaluation->office_rooms }}</td></tr>@endif
                @if(($evaluation->parking_spaces ?? null) !== null)<tr><td>Vagas</td><td>{{ $evaluation->parking_spaces }}</td></tr>@endif
                @if(($evaluation->total_area ?? null) !== null)<tr><td>Área Total</td><td>{{ $evaluation->total_area }} m²</td></tr>@endif
            </table>
            @if(isset($d['layout']))
            <div class="mb-10">
                <div class="feature-label">Layout</div>
                <table class="info-table">
                    @foreach($d['layout'] as $k => $v)
                        <tr><td>{{ $label($k) }}</td><td>{{ is_array($v) ? implode(', ', $v) : $fmt($v) }}</td></tr>
                    @endforeach
                </table>
            </div>
            @endif
            @if(isset($d['instalacoes']))
            <div class="mb-10">
                <div class="feature-label">Instalações</div>
                <table class="info-table">
                    @foreach($d['instalacoes'] as $k => $v)
                        <tr><td>{{ $label($k) }}</td><td>{{ $fmt($v) }}</td></tr>
                    @endforeach
                </table>
            </div>
            @endif
        </div>
        @php $remaining = array_diff_key($d, array_flip($used)); @endphp
        @if(!empty($remaining))
        <div class="card">
            <div class="card-header">Outros Detalhes</div>
            <table class="info-table">
                @foreach($remaining as $k => $v)
                    <tr><td>{{ $label($k) }}</td><td>@if(is_array($v)) {{ implode(', ', array_map(fn($i)=> is_array($i)? json_encode($i, JSON_UNESCAPED_UNICODE) : $i, $v)) }} @else {{ $fmt($v) }} @endif</td></tr>
                @endforeach
            </table>
        </div>
        @endif
    @elseif(!empty($d) && $evaluation->property_type === 'rural')
        @php $used = ['rebanho']; @endphp
        <div class="card">
            <div class="card-header">Detalhes Rurais</div>
            <table class="info-table">
                @if(($evaluation->rural_total_area ?? null) !== null)<tr><td>Área Total</td><td>{{ $evaluation->rural_total_area }} hectares</td></tr>@endif
                @if(($evaluation->has_construction ?? null) !== null)<tr><td>Construções</td><td>{{ $evaluation->has_construction ? 'Possui' : 'Não possui' }}</td></tr>@endif
                @if(($evaluation->construction_types_text ?? null))<tr><td>Tipos de Construção</td><td>{{ $evaluation->construction_types_text }}</td></tr>@endif
                @if(($evaluation->has_farming ?? null) !== null)<tr><td>Lavoura</td><td>{{ $evaluation->has_farming ? 'Possui' : 'Não possui' }}</td></tr>@endif
                @if(($evaluation->farming_types_text ?? null))<tr><td>Tipos de Lavoura</td><td>{{ $evaluation->farming_types_text }}</td></tr>@endif
                @if(($evaluation->water_source_label ?? null))<tr><td>Fonte de Água</td><td>{{ $evaluation->water_source_label }}</td></tr>@endif
                @if(($evaluation->water_source_details ?? null))<tr><td>Detalhes da Fonte</td><td>{{ $evaluation->water_source_details }}</td></tr>@endif
            </table>
            @if(isset($d['rebanho']))
            <div class="mb-10">
                <div class="feature-label">Rebanho</div>
                <table class="info-table">
                    @if(($d['rebanho']['especie'] ?? null))<tr><td>Espécie</td><td>{{ $d['rebanho']['especie'] }}</td></tr>@endif
                    @if(($d['rebanho']['faixa_etaria'] ?? null))<tr><td>Faixa Etária</td><td>{{ $d['rebanho']['faixa_etaria'] }}</td></tr>@endif
                    @if(($d['rebanho']['raca'] ?? null))<tr><td>Raça</td><td>{{ $d['rebanho']['raca'] }}</td></tr>@endif
                    @if(($d['rebanho']['sexo'] ?? null))<tr><td>Sexo Predominante</td><td>{{ $d['rebanho']['sexo'] }}</td></tr>@endif
                    @if(array_key_exists('quantidade_machos', $d['rebanho']))<tr><td>Machos</td><td>{{ $d['rebanho']['quantidade_machos'] }}</td></tr>@endif
                    @if(array_key_exists('quantidade_femeas', $d['rebanho']))<tr><td>Fêmeas</td><td>{{ $d['rebanho']['quantidade_femeas'] }}</td></tr>@endif
                </table>
            </div>
            @endif
        </div>
        @php $remaining = array_diff_key($d, array_flip($used)); @endphp
        @if(!empty($remaining))
        <div class="card">
            <div class="card-header">Outros Detalhes</div>
            <table class="info-table">
                @foreach($remaining as $k => $v)
                    <tr><td>{{ $label($k) }}</td><td>@if(is_array($v)) {{ implode(', ', array_map(fn($i)=> is_array($i)? json_encode($i, JSON_UNESCAPED_UNICODE) : $i, $v)) }} @else {{ $fmt($v) }} @endif</td></tr>
                @endforeach
            </table>
        </div>
        @endif
    @elseif(!empty($d))
        <div class="card">
            <div class="card-header">Detalhes</div>
            <table class="info-table">
                @foreach($d as $k => $v)
                    <tr><td>{{ $label($k) }}</td><td>@if(is_array($v)) {{ implode(', ', array_map(fn($i)=> is_array($i)? json_encode($i, JSON_UNESCAPED_UNICODE) : $i, $v)) }} @else {{ $fmt($v) }} @endif</td></tr>
                @endforeach
            </table>
        </div>
    @endif

    @if($evaluation->observations)
    <div class="card">
        <div class="card-header">Observações Técnicas</div>
        <div class="observation-box">{{ $evaluation->observations }}</div>
    </div>
    @endif

    <div class="footer">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="appraiser-info">
                {{ $header['appraiser']['name'] ?? $evaluation->appraiser }}
                @if(($header['appraiser']['registry'] ?? null)) - Registro: {{ $header['appraiser']['registry'] }} @endif
            </div>
        </div>
        <div class="system-info">
            Documento gerado eletronicamente pelo sistema Propriedades na Mão em {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>
</body>
</html>