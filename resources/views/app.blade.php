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

        /* LAYOUT PRINCIPAL - 3 COLUMNAS EN ESCRITORIO */
        .grid-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 32px;
        }

        @media (min-width: 1024px) {
            .grid-container {
                grid-template-columns: 1fr 1fr 1fr;
            }
        }

        /* ESTILOS DE PANELES */
        .panel {
            background-color: #ffffff;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1),
                        0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border-radius: 24px;
            padding: 40px;
            text-align: center;
            border: 4px solid #e5e7eb;
            display: flex;
            flex-direction: column;
        }

        .titulo {
            font-size: 28px;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 24px;
            letter-spacing: 0.025em;
        }

        /* PANEL 1: TURNO ACTUAL */
        .turno-box {
            background-color: #00B5B5;
            color: #ffffff;
            border-radius: 12px;
            padding: 24px 16px;
            margin-bottom: 32px;
            box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);
        }

        .numero-turno {
            font-size: 56px;
            font-weight: 900;
            margin-top: 8px;
            line-height: 1;
        }

        @media (min-width: 768px) {
            .numero-turno {
                font-size: 64px;
            }
        }

        .paciente-info {
            color: #1f2937;
        }

        .paciente-label {
            font-size: 20px;
            font-weight: 300;
            margin-bottom: 8px;
        }

        .paciente-nombre {
            font-size: 28px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.125em;
        }

        /* TABLAS */
        .tabla-container {
            flex: 1;
            overflow-y: auto;
            max-height: 600px;
        }

        .tabla {
            width: 100%;
            border-collapse: collapse;
            background-color: #ffffff;
        }

        .tabla thead {
            background-color: #0ea5e9;
            color: #ffffff;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .tabla th {
            padding: 12px 8px;
            font-weight: 600;
            text-align: left;
            border-bottom: 2px solid #0284c7;
            font-size: 14px;
        }

        .tabla td {
            padding: 12px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        .tabla tbody tr:hover {
            background-color: #f0f9ff;
        }

        .tabla tbody tr:last-child td {
            border-bottom: none;
        }

        /* TABLA DE MÉDICOS - Color diferente */
        .tabla-medicos thead {
            background-color: #10b981;
        }

        .tabla-medicos th {
            border-bottom: 2px solid #059669;
        }

        .tabla-medicos tbody tr:hover {
            background-color: #f0fdf4;
        }

        .mensaje-sin-turnos {
            text-align: center;
            color: #9ca3af;
            margin-top: 16px;
            font-size: 14px;
        }

        /* BADGE DE ESTADO */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-activo {
            background-color: #d1fae5;
            color: #065f46;
        }

        .badge-espera {
            background-color: #fef3c7;
            color: #92400e;
        }

        /* RESPONSIVO */
        @media (max-width: 1023px) {
            .panel {
                padding: 24px;
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

            .tabla th,
            .tabla td {
                padding: 8px 6px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    
    <div class="container">
        <div class="grid-container">
            
            <!-- ============================================ -->
            <!-- PANEL 1: TURNO ACTUAL -->
            <!-- ============================================ -->
            <div class="panel">
             

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
            <!-- PANEL 2: PACIENTES LLAMADOS -->
            <!-- ============================================ -->
            <div class="panel">
              

                <div class="tabla-container">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>Turno</th>
                                <th>Paciente</th>
                                <th>Hora</th>
                            </tr>
                        </thead>
                        <tbody id="tablaTurnos">
                            <!-- Se llena dinámicamente -->
                        </tbody>
                    </table>
                </div>

                <p id="mensajeSinTurnos" class="mensaje-sin-turnos" style="display: none;">
                    No hay pacientes llamados.
                </p>
            </div>

            <!-- ============================================ -->
            <!-- PANEL 3: TURNOS POR MÉDICOS -->
            <!-- ============================================ -->
            <div class="panel">
               

                <div class="tabla-container">
                    <table class="tabla tabla-medicos">
                        <thead>
                            <tr>
                                <th>Turno</th>
                                <th>Consultorio</th>
                                <th>Paciente</th>
                                <th>Hora</th>
                            </tr>
                        </thead>
                        <tbody id="tablaTurnosMedicos">
                            <!-- Se llena dinámicamente -->
                        </tbody>
                    </table>
                </div>

                <p id="mensajeSinTurnosMedicos" class="mensaje-sin-turnos" style="display: none;">
                    No hay turnos asignados a médicos.
                </p>
            </div>

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
        // FUNCIÓN 2: OBTENER PACIENTES LLAMADOS
        // ============================================
        function obtenerTurnosLlamados() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '/api/turnos-llamados');
            
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
                        
                        data.forEach(function(t) {
                            var tr = document.createElement('tr');
                            
                            // Columna Turno
                            var tdTurno = document.createElement('td');
                            tdTurno.style.fontWeight = 'bold';
                            tdTurno.textContent = t.numero_turno;
                            
                            // Columna Paciente
                            var tdPaciente = document.createElement('td');
                            tdPaciente.textContent = (t.paciente && t.paciente.nombre ? 
                                t.paciente.nombre + ' ' + (t.paciente.apellido || '') : '-');
                            
                            // Columna Hora
                            var tdHora = document.createElement('td');
                            var fecha = new Date(t.updated_at);
                            var horas = fecha.getHours() % 12 || 12;
                            var minutos = fecha.getMinutes().toString().padStart(2, '0');
                            var ampm = fecha.getHours() >= 12 ? 'PM' : 'AM';
                            tdHora.textContent = horas + ':' + minutos + ' ' + ampm;
                            
                            tr.appendChild(tdTurno);
                            tr.appendChild(tdPaciente);
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
        // FUNCIÓN 3: OBTENER TURNOS POR MÉDICOS (NUEVA)
        // ============================================
   function obtenerTurnosMedicos() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '/api/turnos-medicos');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var data = JSON.parse(xhr.responseText);
                    var tbody = document.getElementById('tablaTurnosMedicos');
                    var mensaje = document.getElementById('mensajeSinTurnosMedicos');
                    
                    if (data.length === 0) {
                        tbody.innerHTML = '';
                        mensaje.style.display = 'block';
                    } else {
                        mensaje.style.display = 'none';
                        tbody.innerHTML = '';
                        
                        data.forEach(function(t) {
                            var tr = document.createElement('tr');
                            
                            // Columna Turno
                            var tdTurno = document.createElement('td');
                            tdTurno.style.fontWeight = 'bold';
                            tdTurno.textContent = t.numero_turno || '-';
                            
                            // Columna Consultorio
                            var tdConsultorio = document.createElement('td');
                            tdConsultorio.style.fontWeight = '600';
                            tdConsultorio.style.color = '#059669';
                            
                            // Verificar si consultorio es un objeto con nombre, o solo el ID/código
                            if (t.consultorio && typeof t.consultorio === 'object' && t.consultorio.nombre) {
                                tdConsultorio.textContent = t.consultorio.nombre;
                            } else if (t.consultorio && typeof t.consultorio === 'string') {
                                tdConsultorio.textContent = t.consultorio;
                            } else if (t.fk_consultorio) {
                                tdConsultorio.textContent = 'Consultorio ' + t.fk_consultorio;
                            } else {
                                tdConsultorio.textContent = '-';
                            }
                            
                            // Columna Paciente
                            var tdPaciente = document.createElement('td');
                            tdPaciente.textContent = (t.paciente && t.paciente.nombre ? 
                                t.paciente.nombre + ' ' + (t.paciente.apellido || '') : '-');
                            
                            // Columna Hora
                            var tdHora = document.createElement('td');
                            if (t.updated_at) {
                                var fecha = new Date(t.updated_at);
                                var horas = fecha.getHours() % 12 || 12;
                                var minutos = fecha.getMinutes().toString().padStart(2, '0');
                                var ampm = fecha.getHours() >= 12 ? 'PM' : 'AM';
                                tdHora.textContent = horas + ':' + minutos + ' ' + ampm;
                            } else {
                                tdHora.textContent = '-';
                            }
                            
                            tr.appendChild(tdTurno);
                            tr.appendChild(tdConsultorio);
                            tr.appendChild(tdPaciente);
                            tr.appendChild(tdHora);
                            tbody.appendChild(tr);
                        });
                    }
                }
            };
            
            xhr.onerror = function() {
                console.error('Error al obtener turnos de consultorios');
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