<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Turno {{ $numero_turno }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            padding: 5px 10px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
        }

        .header h1 {
            font-size: 32px;
            color: #000;
            margin-bottom: 10px;
        }

        .numero-turno {
            font-size: 48px;
            font-weight: bold;
            color: #000;
            text-align: center;
            margin: 40px 0;
            padding: 20px;
            border: 2px solid #000;
            border-radius: 10px;
            background-color: #fff;
        }

        .info-section {
            margin: 10px 0;
            padding: 10px;
            background-color: #f9fafb;
            border-radius: 8px;
        }

        .info-row {
            display: flex;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .info-label {
            font-weight: bold;
            width: 150px;
            color: #4b5563;
        }

        .info-value {
            color: #1f2937;
        }

        .divider {
            height: 2px;
            background-color: #e5e7eb;
            margin: 30px 0;
        }

        .instrucciones {
            text-align: center;
            margin: 10px 0;
            padding: 10px;
            background-color: #fff;
            border-left: 4px solid #000000;
            border-radius: 4px;
        }

        .instrucciones p {
            font-size: 14px;
            color: #000000;
            margin: 8px 0;
            line-height: 1.6;
        }

        .footer {
            text-align: center;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 12px;
        }

        .logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo h2 {
            color: #000;
            font-size: 24px;
        }

        .logo img {
            width: 150px;
            /* Ajusta para ancho máximo del ticket 80mm */
            height: auto;
            /* Mantiene proporción */
            display: block;
            margin: 0 auto;
        }
    </style>
</head>

<body>

    <div class="logo">
        <h1></h1>
    </div>

    <div class="header">
        <h1>NUMERO DE TURNO</h1>
    </div>

    <div class="numero-turno">
        {{ $numero_turno }}
    </div>

    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Fecha y hora:</span>
            <span class="info-value">{{ $fecha }}</span>
        </div>
    </div>

    <div class="divider"></div>

    <div class="instrucciones">
        <p><strong> IMPORTANTE</strong></p>
        <p>Por favor conserve este turno y preséntelo en el área de urgencias.</p>
        <p>Será atendido en orden de llegada según la prioridad médica establecida por el personal de salud.</p>
        <p>Si tiene alguna emergencia médica grave, notifique inmediatamente al personal.</p>
    </div>

    <div class="footer">
        <p>Generado el {{ $fecha }}</p>
    </div>
</body>

</html>