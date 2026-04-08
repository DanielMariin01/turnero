<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Alerta de Contingencia</title>
</head>

<body style="margin:0; padding:0; background-color:#f4f4f4; font-family: Arial, sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f4f4;">
        <tr>
            <td align="center">

                <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color:#ffffff;">

                    <!-- HEADER -->
                    <tr>
                        <td style="background-color:#00B5B5; padding:20px;">
                            <p style="margin:0; color:#ffffff; font-size:18px; font-weight:bold;">
                                ALERTA DE CONTINGENCIA
                            </p>
                            <p style="margin:5px 0 0 0; color:#E0F7F7; font-size:12px;">
                                Clinica central del eje — Triage Urgencias
                            </p>
                        </td>
                    </tr>

                    <!-- BODY -->
                    <tr>
                        <td style="padding:20px;">

                            <p style="color:#333333; font-size:14px;">
                                Se ha detectado una situación que requiere atención inmediata en el área de
                                <strong style="color:#00B5B5;">Triage de Urgencias</strong>.
                            </p>

                            <!-- PACIENTES -->
                            <table width="100%" cellpadding="10" cellspacing="0" border="0"
                                style="margin-bottom:15px;">
                                <tr>
                                    <td style="background-color:#f9f9f9; border-left:4px solid #00B5B5;">
                                        <p style="margin:0; font-size:12px; color:#666;">
                                            PACIENTES EN ESPERA
                                        </p>
                                        <p style="margin:5px 0 0 0; font-size:22px; font-weight:bold; color:#00B5B5;">
                                            {{ $cantidadEspera }}
                                        </p>

                                        @if ($cantidadEspera >= 3)
                                            <p style="font-size:12px; color:#008787;">
                                                Se superó el límite de pacientes
                                            </p>
                                        @endif

                                    </td>
                                </tr>
                            </table>

                            <!-- TIEMPO -->
                            <table width="100%" cellpadding="10" cellspacing="0" border="0"
                                style="margin-bottom:15px;">
                                <tr>
                                    <td style="background-color:#f9f9f9; border-left:4px solid #00B5B5;">
                                        <p style="margin:0; font-size:12px; color:#666;">
                                            NUMERO DE TURNO
                                        </p>
                                        <p style="margin:5px 0 0 0; font-size:22px; font-weight:bold; color:#00B5B5;">
                                            {{ $numero_turno }}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <table width="100%" cellpadding="10" cellspacing="0" border="0"
                                style="margin-bottom:15px;">
                                <tr>
                                    <td style="background-color:#f9f9f9; border-left:4px solid #00B5B5;">
                                        <p style="margin:0; font-size:12px; color:#666;">
                                            TIEMPO DE ESPERA
                                        </p>
                                        <p style="margin:5px 0 0 0; font-size:22px; font-weight:bold; color:#00B5B5;">
                                            {{ $tiempoFormateado }}
                                        </p>

                                        @if ($maxEspera >= 10)
                                            <p style="font-size:12px; color:#008787;">
                                                Tiempo excedido
                                            </p>
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            <!-- DETALLES -->
                            <table width="100%" cellpadding="8" cellspacing="0" border="0"
                                style="background-color:#f9f9f9;">
                                <tr>
                                    <td style="font-size:12px; color:#333;">
                                        <strong>Motivo:</strong> {{ $motivo }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-size:12px; color:#333;">
                                        <strong>Detalle:</strong> {{ $detalle }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-size:12px; color:#333;">
                                        <strong>Fecha:</strong> {{ now()->format('d/m/Y H:i:s') }}
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- FOOTER -->
                    <tr>
                        <td style="background-color:#005858; padding:15px; text-align:center;">
                            <p style="margin:0; color:#B3EAEA; font-size:11px;">
                                Este mensaje fue generado automáticamente.<br>
                                Por favor no responder.
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>
