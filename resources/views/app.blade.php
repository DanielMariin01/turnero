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
            background-color: #f3f4f6;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
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
            background-color: #00B5B5;
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
            font-size: 14px;
            white-space: nowrap;
        }

        .tabla-horizontal td {
            padding: 12px 16px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
            text-align: center;
            white-space: nowrap;
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
            .panel, .panel-horizontal {
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
                font-size: 24px;
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
                    Turno Actual
                </h1>

                <div class="turno-box">
                    <p id="numeroTurno" class="numero-turno">
                        -
                    </p>
                </div>

                <div class="paciente-info">
                    <p class="paciente-label">Paciente:</p>
                    <p id="nombrePaciente" class="paciente-nombre">
                        -
                    </p>
                </div>
            </div>

            <!-- ============================================ -->
            <!-- PANEL 2: CONSULTORIO ACTUAL -->
            <!-- ============================================ -->
            <div class="panel">
                <h1 class="titulo">
                    Consultorio Actual
                </h1>

                <!-- Número de Turno -->
                <div class="info-section">
                    <p class="info-label" >Turno:</p>
                    <p id="numeroTurnoConsultorio" class="info-value">
                        -
                    </p>
                </div>

                <!-- Nombre del Consultorio (GRANDE) -->
                <div class="turno-box" style="background-color: #00B5B5;">
                    <p id="nombreConsultorio" class="numero-turno">
                        -
                    </p>
                </div>

                <!-- Nombre del Paciente -->
                <div class="paciente-info">
                    <p class="paciente-label">Paciente:</p>
                    <p id="nombrePacienteConsultorio" class="paciente-nombre">
                        -
                    </p>
                </div>
            </div>

        </div>

        <!-- FILA INFERIOR: TABLA HORIZONTAL -->
        <div class="panel-horizontal">
            <h2 class="titulo">
                Pacientes Llamados
            </h2>

            <div class="tabla-horizontal-container">
                <table class="tabla-horizontal">
                    <thead>
                        <tr id="tablaTurnosHeader">
                            <!-- Se llena dinámicamente con los encabezados -->
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
    <audio id="audio" preload="auto" muted>
        <source src="/audio/audio1.mp3" type="audio/mpeg">
    </audio>

    <script>
        var turnoAnterior = null;
        var audio = document.getElementById('audio');
        var sonidoListo = false;

        // ============================================
        // CONFIGURACIÓN DE AUDIO
        // ============================================
        
        // 🔊 Intentar cargar audio automáticamente al inicio
        window.addEventListener('load', function() {
            audio.load();
            
            audio.play().then(function() {
                console.log('Audio activado automáticamente');
                sonidoListo = true;
                audio.pause();
                audio.currentTime = 0;
            }).catch(function() {
                console.log('Audio requiere interacción del usuario');
            });
        });

        // Activar audio con cualquier clic en la página
        document.addEventListener('click', function activarAudio() {
            if (!sonidoListo) {
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
        }, { once: true });

        // ============================================
        // FUNCIÓN 1: OBTENER TURNO ACTUAL
        // ============================================
        function obtenerTurno() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '/api/turno-ultimo');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var data = JSON.parse(xhr.responseText);
                    
                    // Reproducir sonido si cambió el turno
                    if (turnoAnterior && turnoAnterior.numero_turno !== data.numero_turno && sonidoListo) {
                        console.log('Nuevo turno detectado - Reproduciendo sonido');
                        audio.currentTime = 0;
                        audio.play().catch(function(err) {
                            console.error('Error reproduciendo audio:', err);
                        });
                    }
                    
                    // Actualizar pantalla
                    document.getElementById('numeroTurno').textContent = data.numero_turno || '-';
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
            xhr.open('GET', '/api/turnos-llamados');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var data = JSON.parse(xhr.responseText);
                    var thead = document.getElementById('tablaTurnosHeader');
                    var tbody = document.getElementById('tablaTurnos');
                    var mensaje = document.getElementById('mensajeSinTurnos');
                    
                    if (data.length === 0) {
                        thead.innerHTML = '';
                        tbody.innerHTML = '';
                        mensaje.style.display = 'block';
                    } else {
                        mensaje.style.display = 'none';
                        
                        // Limpiar encabezados y datos
                        thead.innerHTML = '';
                        tbody.innerHTML = '';
                        
                        // Crear encabezados (repetidos para cada turno)
                        data.forEach(function() {
                            var thTurno = document.createElement('th');
                            thTurno.textContent = 'Turno';
                            thead.appendChild(thTurno);
                            
                            var thPaciente = document.createElement('th');
                            thPaciente.textContent = 'Paciente';
                            thead.appendChild(thPaciente);
                            
                            var thHora = document.createElement('th');
                            thHora.textContent = 'Hora';
                            thead.appendChild(thHora);
                        });
                        
                        // Crear fila única con todos los datos
                        var tr = document.createElement('tr');
                        
                        data.forEach(function(t) {
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
                            
                            // Columna Hora
                            var tdHora = document.createElement('td');
                            var fecha = new Date(t.updated_at);
                            var horas = fecha.getHours() % 12 || 12;
                            var minutos = fecha.getMinutes().toString().padStart(2, '0');
                            var ampm = fecha.getHours() >= 12 ? 'PM' : 'AM';
                            tdHora.textContent = horas + ':' + minutos + ' ' + ampm;
                            tr.appendChild(tdHora);
                        });
                        
                        tbody.appendChild(tr);
                    }
                }
            };
            
            xhr.onerror = function() {
                console.error('Error al obtener turnos llamados');
            };
            
            xhr.send();
        }

        // ============================================
        // FUNCIÓN 3: OBTENER TURNO ACTUAL DE CONSULTORIO
        // ============================================
        function obtenerTurnosMedicos() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '/api/turnos-medicos');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var data = JSON.parse(xhr.responseText);
                    
                    // Obtener el primer turno (más reciente)
                    if (data.length > 0) {
                        var turno = data[0];
                        
                        // Actualizar Número de Turno
                        document.getElementById('numeroTurnoConsultorio').textContent = 
                            turno.numero_turno || '-';
                        
                        // Actualizar Nombre del Consultorio (GRANDE)
                        var nombreConsultorio = '-';
                        if (turno.consultorio && typeof turno.consultorio === 'object' && turno.consultorio.nombre) {
                            nombreConsultorio = turno.consultorio.nombre;
                        } else if (turno.consultorio && typeof turno.consultorio === 'string') {
                            nombreConsultorio = turno.consultorio;
                        } else if (turno.fk_consultorio) {
                            nombreConsultorio = 'Consultorio ' + turno.fk_consultorio;
                        }
                        document.getElementById('nombreConsultorio').textContent = nombreConsultorio;
                        
                        // Actualizar Nombre del Paciente
                        var nombrePaciente = '-';
                        if (turno.paciente && turno.paciente.nombre) {
                            nombrePaciente = turno.paciente.nombre + ' ' + (turno.paciente.apellido || '');
                        }
                        document.getElementById('nombrePacienteConsultorio').textContent = nombrePaciente;
                        
                    } else {
                        // Si no hay turnos
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
        }

        // ============================================
        // EJECUCIÓN INICIAL Y ACTUALIZACIÓN PERIÓDICA
        // ============================================
        
        // Ejecutar al cargar
        obtenerTurno();
        obtenerTurnosLlamados();
        obtenerTurnosMedicos();

        // Actualizar cada 2 segundos
        setInterval(obtenerTurno, 2000);
        setInterval(obtenerTurnosLlamados, 2000);
        setInterval(obtenerTurnosMedicos, 2000);
    </script>

</body>
</html>