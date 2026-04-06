<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Turnos</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-image: url('{{ asset("imagenes/fondo.png") }}');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            font-family: Arial, sans-serif;
        }

        .container {
            padding: 40px;
            max-width: 90rem;
            margin: 0 auto;
        }

        /* LAYOUT PRINCIPAL - 2 FILAS */
        .grid-superior {
            display: grid;
            grid-template-columns: 1fr;
            gap: 32px;
            margin-bottom: 32px;
        }

        @media (min-width: 1024px) {
            .grid-superior {
                grid-template-columns: 1fr 1fr;
            }
        }

        /* ESTILOS DE PANELES */
        .panel {
            background-color: #ffffff;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1),
                0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border-radius: 24px;
            padding: 32px;
            text-align: center;
            border: 4px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            min-height: 380px;
        }

        .panel-horizontal {
            background-color: #ffffff;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1),
                0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border-radius: 24px;
            padding: 32px;
            text-align: center;
            border: 4px solid #e5e7eb;
            width: 100%;
            /* Ocupa todo el ancho */
            max-width: 100%;
            /* Evita que se limite el ancho */
            margin: 0 auto;

        }

        .titulo {
            font-size: 24px;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 20px;
            letter-spacing: 0.025em;
        }

        /* PANEL: TURNO ACTUAL Y CONSULTORIO */
        .turno-box {
            background-color: #117dacff;
            color: #ffffff;
            border-radius: 12px;
            padding: 20px 16px;
            margin-bottom: 20px;
            box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);
        }

        .numero-turno {
            font-size: 48px;
            font-weight: 900;
            margin-top: 4px;
            line-height: 1;
        }

        @media (min-width: 768px) {
            .numero-turno {
                font-size: 52px;
            }
        }

        .paciente-info {
            color: #1f2937;
            margin-top: auto;
        }

        .paciente-label {
            font-size: 16px;
            font-weight: 300;
            margin-bottom: 6px;
        }

        .paciente-nombre {
            font-size: 22px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.125em;
        }

        /* INFO SECTIONS */
        .info-section {
            margin-bottom: 16px;
        }

        .info-label {
            font-size: 16px;
            font-weight: 300;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .info-value {
            font-size: 28px;
            font-weight: 800;
            color: #1f2937;
        }

        /* TABLA HORIZONTAL */
        .tabla-horizontal-container {
            overflow-x: auto;
            width: 100%;
        }

        .tabla-horizontal {
            width: 100%;
            border-collapse: collapse;
            background-color: #ffffff;
        }

        .tabla-horizontal thead {
            background-color: #00B5B5;
            color: #ffffff;
        }

        .tabla-horizontal th {
            padding: 12px 16px;
            font-weight: 600;
            text-align: center;
            border-bottom: 2px solid #00B5B5;
            border-right: 1px solid #e5e7eb;
            font-size: 40px;
            white-space: nowrap;
            background-color: #00B5B5;
            color: #ffffff;
        }

        .tabla-horizontal th:last-child {
            border-right: none;
        }

        .tabla-horizontal td {
            padding: 12px 16px;
            border-bottom: 1px solid #e5e7eb;
            border-right: 1px solid #e5e7eb;
            font-size: 40px;
            text-align: center;
            white-space: nowrap;
        }

        .tabla-horizontal td:last-child {
            border-right: none;
        }

        .tabla-horizontal tbody tr:hover {
            background-color: #f0f9ff;
        }

        .tabla-horizontal tbody tr:last-child td {
            border-bottom: none;
        }

        .mensaje-sin-turnos {
            text-align: center;
            color: #9ca3af;
            margin-top: 16px;
            font-size: 14px;
        }

        /* RESPONSIVO */
        @media (max-width: 1023px) {

            .panel,
            .panel-horizontal {
                padding: 24px;
                min-height: auto;
            }

            .titulo {
                font-size: 24px;
            }

            .numero-turno {
                font-size: 48px;
            }

            .paciente-nombre {
                font-size: 48px;
            }

            .tabla-horizontal th,
            .tabla-horizontal td {
                padding: 10px 12px;
                font-size: 12px;
            }

            .info-value {
                font-size: 28px;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <!-- FILA SUPERIOR: DOS PANELES -->
        <div class="grid-superior">

            <!-- ============================================ -->
            <!-- PANEL 1: TURNO ACTUAL -->
            <!-- ============================================ -->
            <div class="panel">
                <h1 class="titulo">
                    Turno
                </h1>
                <div class="turno-box">
                    <p id="numeroTurno" class="numero-turno">
                        -
                    </p>
                </div>
                <div class="turno-box" style="background-color: #00B5B5;">
                    <p id="nombreModulo" class="numero-turno">
                        -
                    </p>
                    <p id="nombrePaciente" class="numero-turno">
                </div>
            </div>


        </div>

        <!-- FILA INFERIOR: TABLA HORIZONTAL -->
        <div class="panel-horizontal">


            <div class="tabla-horizontal-container">
                <table class="tabla-horizontal">
                    <thead>
                        <tr>
                            <th>Turno</th>
                            <th>Paciente</th>
                            <th>ubicación</th>
                            <th>Hora</th>
                        </tr>
                    </thead>
                    <tbody id="tablaTurnos">
                        <!-- Se llena dinámicamente con los datos -->
                    </tbody>
                </table>
            </div>

            <p id="mensajeSinTurnos" class="mensaje-sin-turnos" style="display: none;">
                No hay pacientes llamados.
            </p>
        </div>
    </div>

    <!-- Audio con autoplay y muted inicial -->
    <!-- Audio con autoplay y muted inicial para garantizar carga -->
    <audio id="audio" preload="auto" muted>
        <source src="/audio/audio1.mp3" type="audio/mpeg">
    </audio>



    <script>
        var turnoAnterior = null;
        var audio = document.getElementById('audio');
        var sonidoListo = false;
        var ultimoTurnoConsultorio = null;

        // ============================================
        // FUNCIÓN 3: OBTENER TURNO ACTUAL DE CONSULTORIO
        // ============================================
        //function obtenerTurnosMedicos() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '/api/turnoUltimoQuimioterapia');

        xhr.onload = function() {
            if (xhr.status === 200) {
                var data = JSON.parse(xhr.responseText);

                if (data.length > 0) {
                    var turno = data[0];

                    // Guardamos el turno actual
                    var turnoActual = turno.numero_turno || '-';

                    // ⭐ DETECTAR SI CAMBIÓ EL NÚMERO DE TURNO ⭐
                    if (ultimoTurnoConsultorio !== null && ultimoTurnoConsultorio !== turnoActual) {
                        console.log("Cambio de turno detectado: " + turnoActual);

                        // REPRODUCIR AUDIO
                        try {
                            audio.muted = false;
                            audio.currentTime = 0;
                            audio.play()
                                .then(() => console.log("Audio reproducido"))
                                .catch(err => console.warn("No se pudo reproducir el audio:", err));
                        } catch (e) {
                            console.error("Error al reproducir el audio:", e);
                        }
                    }

                    // Actualizar el último turno consultorio
                    ultimoTurnoConsultorio = turnoActual;

                    // Actualizar Número de Turno en pantalla
                    document.getElementById('numeroTurnoConsultorio').textContent = turnoActual;

                    // --- Nombre grande del consultorio ---
                    var nombreConsultorio = '-';
                    if (turno.consultorio && typeof turno.consultorio === 'object' && turno.consultorio.nombre) {
                        nombreConsultorio = turno.consultorio.nombre;
                    } else if (turno.consultorio && typeof turno.consultorio === 'string') {
                        nombreConsultorio = turno.consultorio;
                    } else if (turno.fk_consultorio) {
                        nombreConsultorio = 'Consultorio ' + turno.fk_consultorio;
                    }
                    document.getElementById('nombreConsultorio').textContent = nombreConsultorio;

                    // --- Nombre del paciente ---
                    var nombrePaciente = '-';
                    if (turno.paciente && turno.paciente.nombre) {
                        nombrePaciente = turno.paciente.nombre + " " + (turno.paciente.apellido || '');
                    }
                    document.getElementById('nombrePacienteConsultorio').textContent = nombrePaciente;

                } else {
                    // No hay turnos
                    document.getElementById('numeroTurnoConsultorio').textContent = '-';
                    document.getElementById('nombreConsultorio').textContent = '-';
                    document.getElementById('nombrePacienteConsultorio').textContent = '-';
                }
            }
        };

        xhr.onerror = function() {
            console.error('Error al obtener turno de consultorio');
        };

        xhr.send();
        //}


        // 🔊 Intentar cargar audio automáticamente al inicio
        window.addEventListener('load', function() {
            audio.load();
            audio.muted = true;

            // Intentar reproducir (algunos navegadores lo permiten)
            audio.play().then(function() {
                console.log('Audio activado automáticamente');
                sonidoListo = true;
                audio.pause();
                audio.currentTime = 0;
            }).catch(function() {
                console.log('Audio requiere interacción del usuario');
                // El sonido se activará en el primer clic
            });
        });

        // Activar audio con cualquier clic en la página
        document.addEventListener('click', function activarAudio() {
            if (!sonidoListo) {
                audio.muted = false;
                audio.play().then(function() {
                    sonidoListo = true;
                    audio.pause();
                    audio.currentTime = 0;
                    console.log('Audio activado por interacción');
                }).catch(function(err) {
                    console.warn('Error activando audio:', err);
                });
                document.removeEventListener('click', activarAudio);
            }
        }, {
            once: true
        });



        // ============================================
        // FUNCIÓN 1: OBTENER TURNO ACTUAL
        // ============================================
        function obtenerTurno() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '/api/turnoUltimoQuimioterapia');

            xhr.onload = function() {
                if (xhr.status === 200) {
                    var data = JSON.parse(xhr.responseText);

                    // Reproducir sonido si cambió el turno
                    var cambioTurno =
                        turnoAnterior &&
                        turnoAnterior.numero_turno !== data.numero_turno;

                    var rellamado =
                        turnoAnterior &&
                        turnoAnterior.llamado_en !== data.llamado_en &&
                        data.llamado_en !== null;

                    if ((cambioTurno || rellamado) && sonidoListo) {
                        console.log('Nuevo llamado detectado - Reproduciendo sonido');
                        audio.currentTime = 0;
                        audio.play().catch(function(err) {
                            console.error('Error reproduciendo audio:', err);
                        });
                    }


                    // Actualizar Número de Turno
                    document.getElementById('numeroTurno').textContent = data.numero_turno || '-';

                    // Actualizar Nombre del Módulo
                    var nombreModulo = '-';
                    if (data.modulo && typeof data.modulo === 'object' && data.modulo.nombre) {
                        nombreModulo = data.modulo.nombre;
                    } else if (data.modulo && typeof data.modulo === 'string') {
                        nombreModulo = data.modulo;
                    } else if (data.fk_modulo) {
                        nombreModulo = 'Módulo ' + data.fk_modulo;
                    }
                    document.getElementById('nombreModulo').textContent = nombreModulo;

                    // Actualizar Nombre del Paciente
                    document.getElementById('nombrePaciente').textContent =
                        (data.nombre || '-') + ' ' + (data.apellido || '');

                    turnoAnterior = data;
                }
            };

            xhr.onerror = function() {
                console.error('Error al obtener turno');
            };

            xhr.send();
        }

        // ============================================
        // FUNCIÓN 2: OBTENER PACIENTES LLAMADOS (TABLA HORIZONTAL)
        // ============================================
        function obtenerTurnosLlamados() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '/api/turnosLlamadosQuimioterapia');

            xhr.onload = function() {
                if (xhr.status === 200) {
                    var data = JSON.parse(xhr.responseText);
                    var tbody = document.getElementById('tablaTurnos');
                    var mensaje = document.getElementById('mensajeSinTurnos');

                    if (data.length === 0) {
                        tbody.innerHTML = '';
                        mensaje.style.display = 'block';
                    } else {
                        mensaje.style.display = 'none';
                        tbody.innerHTML = '';

                        // Crear una fila por cada turno
                        data.forEach(function(t) {
                            var tr = document.createElement('tr');

                            // Columna Turno
                            var tdTurno = document.createElement('td');
                            tdTurno.style.fontWeight = 'bold';
                            tdTurno.textContent = t.numero_turno;
                            tr.appendChild(tdTurno);

                            // Columna Paciente
                            var tdPaciente = document.createElement('td');
                            tdPaciente.textContent = (t.paciente && t.paciente.nombre ?
                                t.paciente.nombre + ' ' + (t.paciente.apellido || '') : '-');
                            tr.appendChild(tdPaciente);

                            // Columna Ubicación (Módulo o Consultorio)
                            var tdUbicacion = document.createElement('td');
                            var ubicacion = '-';

                            // Priorizar Consultorio si existe
                            if (t.consultorio && typeof t.consultorio === 'object' && t.consultorio.nombre) {
                                ubicacion = t.consultorio.nombre;
                            } else if (t.consultorio && typeof t.consultorio === 'string') {
                                ubicacion = t.consultorio;
                            } else if (t.fk_consultorio) {
                                ubicacion = 'Consultorio ' + t.fk_consultorio;
                            }
                            // Si no hay consultorio, mostrar módulo
                            else if (t.modulo && typeof t.modulo === 'object' && t.modulo.nombre) {
                                ubicacion = t.modulo.nombre;
                            } else if (t.modulo && typeof t.modulo === 'string') {
                                ubicacion = t.modulo;
                            } else if (t.fk_modulo) {
                                ubicacion = 'Módulo ' + t.fk_modulo;
                            }

                            tdUbicacion.textContent = ubicacion;
                            tr.appendChild(tdUbicacion);

                            // Columna Hora
                            var tdHora = document.createElement('td');
                            var fecha = new Date(t.updated_at);
                            var horas = fecha.getHours() % 12 || 12;
                            var minutos = fecha.getMinutes().toString().padStart(2, '0');
                            var ampm = fecha.getHours() >= 12 ? 'PM' : 'AM';
                            tdHora.textContent = horas + ':' + minutos + ' ' + ampm;
                            tr.appendChild(tdHora);

                            tbody.appendChild(tr);
                        });
                    }
                }
            };

            xhr.onerror = function() {
                console.error('Error al obtener turnos llamados');
            };

            xhr.send();
        }

        // ============================================
        // EJECUCIÓN INICIAL Y ACTUALIZACIÓN PERIÓDICA
        // ============================================

        // Ejecutar al cargar
        obtenerTurno();
        obtenerTurnosLlamados();

        // Actualizar cada 60 segundos
        setInterval(obtenerTurno, 5000);
        setInterval(obtenerTurnosLlamados, 5000);
    </script>

</body>

</html>