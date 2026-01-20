<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Turno {{ $numero_turno }}</title>
    <style>
        @page {
            margin: 0;
            padding: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 204pt;
            height: 200pt;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        body {
            font-family: 'Arial', sans-serif;
            color: #333;
            position: relative;
        }

        .ticket-wrapper {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }

        .numero-turno {
            font-size: 48pt;
            font-weight: bold;
            color: #000;
            text-align: center;
            padding: 15pt 30pt;
            border: 3pt solid #000;
            border-radius: 10pt;
            background-color: #fff;
            margin-bottom: 12pt;
            line-height: 1;
            display: inline-block;
        }

        .info-row {
            font-size: 9pt;
            color: #555;
            white-space: nowrap;
        }

        .info-value {
            color: #000;
        }
    </style>
</head>

<body>

    <div class="ticket-wrapper">
        <div class="numero-turno">
            {{ $numero_turno }}
        </div>
        
        <div class="info-row">
            <span class="info-label">Fecha y hora:</span>
            <span class="info-value">{{ $fecha }}</span>
        </div>
    </div>

</body>

</html>