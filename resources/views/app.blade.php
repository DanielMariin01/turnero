<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pantalla de Turnos</title>
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
            max-width: 80rem;
            margin: 0 auto;
        }

        .grid-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 32px;
        }

        @media (min-width: 1024px) {
            .grid-container {
                grid-template-columns: 1fr 1fr;
            }
        }

        .panel {
            background-color: #ffffff;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1),
                        0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border-radius: 24px;
            padding: 40px;
            text-align: center;
            border: 4px solid #e5e7eb;
        }

        .titulo {
            font-size: 36px;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 24px;
            letter-spacing: 0.025em;
        }

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

        @media (min-width: 1024px) {
            .numero-turno {
                font-size: 80px;
            }
        }

        .paciente-info {
            color: #1f2937;
        }

        .paciente-label {
            font-size: 24px;
            font-weight: 300;
            margin-bottom: 8px;
        }

        .paciente-nombre {
            font-size: 32px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.125em;
        }

        @media (min-width: 768px) {
            .paciente-nombre {
                font-size: 40px;
            }
        }

        /* Colores personalizados */
        .bg-color {
            background-color: #00B5B5;
        }

        .bg-color-700 {
            background-color: #00B5B5;
        }

   .panel-full {
            width: 100%;
        }

        .titulo {
            font-size: 36px;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 24px;
            letter-spacing: 0.025em;
        }

        .tabla {
            width: 100%;
            border-collapse: collapse;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1),
                        0 4px 6px -2px rgba(0, 0, 0, 0.05);
            background-color: #ffffff;
        }

        .tabla thead {
            background-color: #0ea5e9;
            color: #ffffff;
        }

        .tabla th {
            padding: 12px;
            font-weight: 600;
            text-align: left;
            border-bottom: 2px solid #0ea5e9;
        }

        .tabla td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .tabla tbody tr:hover {
            background-color: #f9fafb;
        }

        .tabla tbody tr:last-child td {
            border-bottom: none;
        }

        .mensaje-sin-turnos {
            text-align: center;
            color: #9ca3af;
            margin-top: 16px;
        }

    </style>
</head>
<body>
    
    <div class="container">
        <!-- CONTENEDOR DIVIDIDO EN 2 COLUMNAS -->
        <div class="grid-container">
            
            <!-- PANEL PRINCIPAL (IZQUIERDA) -->
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

<div class="panel">
                <div class="panel-full">
                    <h2 class="titulo">
                        Pacientes Llamados
                    </h2>

                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>Turno</th>
                                <th>Paciente</th>
                                <th>Hora Llamado</th>
                            </tr>
                        </thead>
                        <tbody id="tablaTurnos">
                            <!-- Se llena dinámicamente -->
                        </tbody>
                    </table>

                    <p id="mensajeSinTurnos" class="mensaje-sin-turnos" style="display: none;">
                        No hay pacientes llamados.
                    </p>
                </div>
            </div>





        </div>
    </div>

    <!-- Audio con autoplay y muted inicial para garantizar carga -->
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

        // Obtener turno actual
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

        // Obtener turnos llamados
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
                            tr.className = 'border-b text-center';
                            
                            // Columna Turno
                            var tdTurno = document.createElement('td');
                            tdTurno.className = 'p-3 font-bold text-xl';
                            tdTurno.textContent = t.numero_turno;
                            
                            // Columna Paciente
                            var tdPaciente = document.createElement('td');
                            tdPaciente.className = 'p-3';
                            tdPaciente.textContent = (t.paciente && t.paciente.nombre ? 
                                t.paciente.nombre + ' ' + (t.paciente.apellido || '') : '-');
                            
                            // Columna Hora
                            var tdHora = document.createElement('td');
                            tdHora.className = 'p-3';
                            var fecha = new Date(t.updated_at);
                         var horas = fecha.getHours() % 12 || 12;
var minutos = fecha.getMinutes().toString().padStart(2, '0');
var ampm = fecha.getHours() >= 12 ? 'PM' : 'AM';
tdHora.textContent = horas + ':' + minutos + ' ' + ampm;
// Resultado: 2:30 PM
                            
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

        // Ejecutar al cargar
        obtenerTurno();
        obtenerTurnosLlamados();

        // Actualizar cada 2 segundos
        setInterval(obtenerTurno, 2000);
        setInterval(obtenerTurnosLlamados, 2000);
    </script>
</body>
</html>