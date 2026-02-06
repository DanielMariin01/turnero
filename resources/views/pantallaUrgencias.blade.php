<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Turnos - Urgencias</title>

    <style>
        /* === ESTILOS SIN CAMBIOS === */
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

        .panel,
        .panel-horizontal {
            background-color: #ffffff;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1),
                0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border-radius: 24px;
            padding: 32px;
            text-align: center;
            border: 4px solid #e5e7eb;
        }

        .panel {
            display: flex;
            flex-direction: column;
            min-height: 380px;
        }

        .titulo {
            font-size: 24px;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 20px;
        }

        .turno-box {
            background-color: #117dacff;
            color: #ffffff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .turno-box-turno {
            background-color: #117dacff;
            color: #ffffff;
            border-radius: 12px;
            padding: 80px;
            margin-bottom: 20px;
            min-height: 50;
        }

        .numero-turno {
            font-size: 52px;
            font-weight: 900;
        }

        .numero-turno-urgencias {
            font-size: 70px;
            font-weight: 900;
        }

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
    </style>
</head>

<body>

    <div class="container">
        <div class="grid-superior">

            <div class="panel">
                <h1 class="titulo">Turno</h1>
                <div class="turno-box-turno">
                    <p id="numeroTurno" class="numero-turno-urgencias">-</p>
                </div>
            </div>

            <div class="panel">
                <h1 class="titulo">Consultorio</h1>
                <div class="turno-box">
                    <p id="numeroTurnoConsultorio" class="numero-turno">-</p>
                </div>
                <div class="turno-box" style="background-color: #00B5B5;">
                    <!-- <p id="nombreConsultorio" class="numero-turno">
                        -
                    </p>-->
                    <p id="nombrePacienteConsultorio" class="numero-turno"> </p>
                </div>
            </div>

        </div>
        <div class="panel-horizontal">


            <div class="tabla-horizontal-container">
                <table class="tabla-horizontal">
                    <thead>
                        <tr>
                            <th>Turno</th>
                            <th>Paciente</th>
                            <th>Ubicación</th>
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
        <source src="{{ asset('audio/audio1.mp3') }}" type="audio/mpeg">
    </audio>

    <script>
        var turnoAnterior = null;
        var audio = document.getElementById('audio');
        var sonidoListo = false;
        var ultimoTurnoConsultorio = null;

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

        /* ================================
           TURNO URGENCIAS (MÓDULO)
        ================================= */
        function obtenerTurnoUrgencias() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '/api/turnoUltimoUrgencias');

            xhr.onload = function() {
                if (xhr.status !== 200) return;

                var data = JSON.parse(xhr.responseText);

                if (!data || !data.numero_turno) {
                    document.getElementById('numeroTurno').textContent = '-';
                    return;
                }

                // Reproducir sonido si cambió el turno
                var cambioTurno =
                    turnoAnterior &&
                    turnoAnterior.numero_turno !== data.numero_turno;

                if (cambioTurno && sonidoListo) {
                    console.log('Nuevo llamado detectado - Reproduciendo sonido');
                    audio.currentTime = 0;
                    audio.play().catch(function(err) {
                        console.error('Error reproduciendo audio:', err);
                    });
                }

                turnoAnterior = data;
                document.getElementById('numeroTurno').textContent = data.numero_turno;
            };

            xhr.onerror = function() {
                console.error('Error al obtener turno urgencias');
            };

            xhr.send();
        }

        /* ================================
           TURNO MÉDICO URGENCIAS
        ================================= */
        function obtenerTurnoMedicoUrgencias() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '/api/turnoMedicoUrgencias');

            xhr.onload = function() {
                if (xhr.status !== 200 || !xhr.responseText) return;

                var data = JSON.parse(xhr.responseText);

                if (!data || !data.numero_turno) {
                    document.getElementById('numeroTurnoConsultorio').textContent = '-';
                    document.getElementById('nombrePacienteConsultorio').textContent = '-';
                    return;
                }

                // Guardamos el turno actual
                var turnoActual = data.numero_turno || '-';

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

                document.getElementById('numeroTurnoConsultorio').textContent = turnoActual;
                console.log('paciente_urgencias:', data.paciente_urgencias);
                var nombrePaciente = data.paciente_urgencias || '-';
                document.getElementById('nombrePacienteConsultorio').textContent = nombrePaciente;
            };

            xhr.onerror = function() {
                console.error('Error al obtener turno de consultorio urgencias');
            };

            xhr.send();
        }

        // ============================================
        // FUNCIÓN: OBTENER PACIENTES LLAMADOS EN AREA DE URGENCIAS (TABLA HORIZONTAL)
        // ============================================
        function turnosLlamadosUrgencias() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '/api/turnosLlamadosUrgencias');

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
                            tdPaciente.textContent = t.paciente_urgencias || '-';
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
        obtenerTurnoUrgencias();
        obtenerTurnoMedicoUrgencias();
        turnosLlamadosUrgencias();

        // Actualizar cada 5 segundos
        setInterval(obtenerTurnoUrgencias, 5000);
        setInterval(obtenerTurnoMedicoUrgencias, 5000);
        setInterval(turnosLlamadosUrgencias, 5000);
    </script>

</body>

</html>