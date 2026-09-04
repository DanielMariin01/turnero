import React, { useState, useRef, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import Swal from "sweetalert2";
import { connectQZ, isQZConnected } from "../qzConfig";

export default function UrgenciasPage() {
    const navigate = useNavigate();
    const procesandoRef = useRef(false);

    const [paciente, setPaciente] = useState(null);
    const [pacienteExistente, setPacienteExistente] = useState(false);
    const [verificando, setVerificando] = useState(false);
    const [scanBuffer, setScanBuffer] = useState('');
    const [mensajeEscaneo, setMensajeEscaneo] = useState('');
    const scanTimeoutRef = useRef(null);
    const [generando, setGenerando] = useState(false);

    useEffect(() => {
        connectQZ().catch(err => console.error("QZ no conectó al iniciar:", err));
    }, []);

    // TIMER DE INACTIVIDAD
    useEffect(() => {
        let timer = setTimeout(() => navigate("/Urgencias"), 30000); 
        const resetTimer = () => {
            clearTimeout(timer);
            timer = setTimeout(() => navigate("/Urgencias"), 30000); 
        };
        window.addEventListener("mousemove", resetTimer);
        window.addEventListener("keydown", resetTimer);
        window.addEventListener("click", resetTimer);
        window.addEventListener("touchstart", resetTimer);
        return () => {
            clearTimeout(timer);
            window.removeEventListener("mousemove", resetTimer);
            window.removeEventListener("keydown", resetTimer);
            window.removeEventListener("click", resetTimer);
            window.removeEventListener("touchstart", resetTimer);
        };
    }, [navigate]);

    // DETECTOR DE ESCANEO
    useEffect(() => {
        const handleScan = (e) => {
            if (e.key === 'Enter') {
                if (scanBuffer.length > 5) {
                    e.preventDefault();
                    procesarCedulaColombia(scanBuffer);
                    setScanBuffer('');
                }
                return;
            }
            if (e.key === 'Shift' || e.key === 'Control' || e.key === 'Alt' || e.key === 'Meta') {
                return;
            }
            if (!e.ctrlKey && !e.altKey && !e.metaKey) {
                let char = e.key;
                if (e.key === 'Tab') {
                    char = '\t';
                    e.preventDefault();
                } else if (e.key === ' ') {
                    char = ' ';
                    e.preventDefault();
                } else if (e.key.length === 1) {
                    e.preventDefault();
                }
                setScanBuffer(prev => prev + char);
                clearTimeout(scanTimeoutRef.current);
                scanTimeoutRef.current = setTimeout(() => setScanBuffer(''), 200);
            }
        };
        window.addEventListener('keydown', handleScan);
        return () => {
            window.removeEventListener('keydown', handleScan);
            clearTimeout(scanTimeoutRef.current);
        };
    }, [scanBuffer]);

    const mostrarMensaje = (mensaje, tipo) => {
        setMensajeEscaneo({ texto: mensaje, tipo });
        setTimeout(() => setMensajeEscaneo(''), 4000);
    };

    // PROCESAR CÉDULA COLOMBIANA → extrae datos, luego verifica contra BD
    const procesarCedulaColombia = (codigoCompleto) => {
        try {
            let partes = [];
            let formatoDetectado = '';

            if (codigoCompleto.includes('\t')) {
                formatoDetectado = 'TAB';
                partes = codigoCompleto.split('\t');
            } else if (codigoCompleto.includes('  ')) {
                formatoDetectado = 'ESPACIOS MÚLTIPLES';
                partes = codigoCompleto.split(/\s{2,}/).map(p => p.trim()).filter(Boolean);
            } else if (codigoCompleto.includes('$')) {
                formatoDetectado = '$';
                partes = codigoCompleto.split('$');
            } else if (codigoCompleto.includes('|')) {
                formatoDetectado = '|';
                partes = codigoCompleto.split('|');
            } else if (codigoCompleto.includes('^')) {
                formatoDetectado = '^';
                partes = codigoCompleto.split('^');
            } else if (codigoCompleto.includes(' ')) {
                formatoDetectado = 'ESPACIO SIMPLE';
                partes = codigoCompleto.split(' ').filter(Boolean);
            } else {
                mostrarMensaje('⚠️ Formato de cédula no reconocido. Intente de nuevo.', 'warning');
                return;
            }

            let numeroDocumento, nombres, apellidos;

            if (formatoDetectado === '$') {
                if (partes.length < 6) {
                    mostrarMensaje('⚠️ Código incompleto. Intente nuevamente.', 'warning');
                    return;
                }
                apellidos = [partes[2]?.trim(), partes[3]?.trim()].filter(Boolean).join(' ');
                nombres = [partes[4]?.trim(), partes[5]?.trim()].filter(Boolean).join(' ');
                numeroDocumento = (partes[1]?.trim() || '').replace(/^0+/, '');
            } else {
                if (partes.length < 5) {
                    mostrarMensaje('⚠️ Código de cédula incompleto. Intente nuevamente.', 'warning');
                    return;
                }
                apellidos = [partes[1]?.trim(), partes[2]?.trim()].filter(Boolean).join(' ');
                nombres = [partes[3]?.trim(), partes[4]?.trim()].filter(Boolean).join(' ');
                numeroDocumento = (partes[0]?.trim() || '').replace(/^0+/, '');
            }

            if (!numeroDocumento || !nombres || !apellidos) {
                mostrarMensaje('⚠️ Datos incompletos en la cédula.', 'warning');
                return;
            }

            verificarPaciente(numeroDocumento, nombres, apellidos);
        } catch (error) {
            console.error('Error procesando cédula:', error);
            mostrarMensaje('❌ Error al procesar la cédula. Intente nuevamente.', 'error');
        }
    };

    // VERIFICAR SI EL PACIENTE YA EXISTE EN LA BASE DE DATOS
    const verificarPaciente = async (numeroDocumento, nombresEscaneados, apellidosEscaneados) => {
        setVerificando(true);
        try {
            const response = await fetch(`/api/pacientes/${numeroDocumento}`);

            if (response.ok) {
                // Paciente existente: usar SUS datos reales (incluido tipo_documento real)
                const data = await response.json();
                setPaciente({
                    nombre: data.nombre,
                    apellido: data.apellido,
                    tipo_documento: data.tipo_documento,
                    numero_documento: data.numero_documento,
                });
                setPacienteExistente(true);
                mostrarMensaje('✅ Paciente encontrado', 'success');
            } else if (response.status === 404) {
                // Paciente nuevo: usar datos del escaneo, SIN tipo_documento (debe seleccionarlo)
                setPaciente({
                    nombre: nombresEscaneados,
                    apellido: apellidosEscaneados,
                    tipo_documento: '',
                    numero_documento: numeroDocumento,
                });
                setPacienteExistente(false);
                mostrarMensaje('✅ Cédula escaneada. Seleccione el tipo de documento.', 'success');
            } else {
                throw new Error(`Error del servidor: ${response.status}`);
            }
        } catch (error) {
            console.error('Error verificando paciente:', error);
            mostrarMensaje('❌ Error al verificar el paciente. Intente nuevamente.', 'error');
        } finally {
            setVerificando(false);
        }
    };

    const handleTipoDocumentoChange = (valor) => {
        setPaciente(prev => ({ ...prev, tipo_documento: valor }));
    };

    // CREAR TURNO DE URGENCIAS + IMPRIMIR
    const crearTurno = async () => {
        if (!paciente || !paciente.tipo_documento || procesandoRef.current) return;
        procesandoRef.current = true;
        setGenerando(true);

        Swal.fire({
            title: "Generando turno...",
            text: "Por favor espera",
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading(),
        });

        let turnoCreado = null;

        try {
            const response = await fetch("/api/turno/urgencias", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(paciente),
            });

            if (!response.ok) throw new Error(`Error del servidor: ${response.status}`);

            const data = await response.json();
            turnoCreado = data.turno;
        } catch (error) {
            console.error("Error creando turno:", error);
            await Swal.fire({
                icon: "error",
                title: "Error",
                text: "No se pudo generar el turno.",
                confirmButtonColor: "#d33",
            });
            procesandoRef.current = false;
            setGenerando(false);
            return;
        }

        if (turnoCreado?.id_turno) {
            try {
                const printResponse = await fetch(`/api/turnos/${turnoCreado.id_turno}/imprimir`);
                if (!printResponse.ok) throw new Error(`Error del servidor: ${printResponse.status}`);

                const printData = await printResponse.json();

                if (printData.ok && printData.comandos) {
                    if (!isQZConnected()) await connectQZ();
                    const config = window.qz.configs.create("TurneroPrinter");
                    const data_print = [{ type: 'raw', format: 'base64', data: printData.comandos }];
                    await window.qz.print(config, data_print);
                }
            } catch (printError) {
                console.error("Error imprimiendo:", printError);
                await Swal.fire({
                    icon: "warning",
                    title: "Turno creado, pero no se pudo imprimir",
                    text: "Verifica la impresora",
                    confirmButtonColor: "#f0ad4e",
                });
                procesandoRef.current = false;
                setGenerando(false);
                resetFormulario();
                setTimeout(() => navigate("/urgencias"), 3000);
                return;
            }
        }

        await Swal.fire({
            icon: "success",
            title: "¡Turno creado!",
            text: "Tu turno de urgencias ha sido registrado.",
            confirmButtonColor: "#3085d6",
        });

        procesandoRef.current = false;
        setGenerando(false);
        resetFormulario();
        setTimeout(() => navigate("/urgencias"), 3000);
    };

    const resetFormulario = () => {
        setPaciente(null);
        setPacienteExistente(false);
        setMensajeEscaneo('');
    };

    return (
        <div className="flex flex-col items-center justify-center h-screen bg-gradient-to-br from-blue-50 to-indigo-100 p-6">
            <div className="w-full max-w-2xl bg-white shadow-2xl rounded-2xl p-8 text-center">
                <h2 className="text-3xl font-bold mb-4 text-indigo-700">Urgencias</h2>

                {!paciente ? (
                    <>
                        <p className="text-lg text-gray-600 mb-6">
                            📱 Escanee el código de barras del reverso de su cédula para pedir su turno
                        </p>
                        {verificando && (
                            <p className="text-indigo-600 font-semibold mb-4">Verificando paciente...</p>
                        )}
                        {mensajeEscaneo && (
                            <div className={`mb-4 p-3 rounded-lg border-l-4 ${mensajeEscaneo.tipo === 'success' ? 'bg-green-50 border-green-500 text-green-800' :
                                mensajeEscaneo.tipo === 'warning' ? 'bg-yellow-50 border-yellow-500 text-yellow-800' :
                                    'bg-red-50 border-red-500 text-red-800'
                                }`}>
                                {mensajeEscaneo.texto}
                            </div>
                        )}
                    </>
                ) : (
                    <>
                        <div className="mb-6 text-left bg-gray-50 rounded-lg p-4">
                            <p><strong>Nombre:</strong> {paciente.nombre} {paciente.apellido}</p>
                            <p><strong>Documento:</strong> {paciente.numero_documento}</p>
                        </div>

                        {!pacienteExistente && (
                            <div className="mb-6 text-left">
                                <label className="block font-semibold text-gray-700 mb-2">
                                    No encontramos su documento registrado. Seleccione el tipo de documento:
                                </label>
                                <select
                                    className="border-2 border-gray-300 p-3 rounded-lg w-full text-base focus:border-indigo-500 focus:outline-none"
                                    value={paciente.tipo_documento}
                                    onChange={(e) => handleTipoDocumentoChange(e.target.value)}
                                >
                                    <option value="">Seleccione...</option>
                                    <option value="CC">Cédula de ciudadanía</option>
                                    <option value="TI">Tarjeta de identidad</option>
                                    <option value="CE">Cédula de extranjería</option>
                                    <option value="PA">Pasaporte</option>
                                    <option value="RC">Registro Civil</option>
                                </select>
                            </div>
                        )}

                        <div className="flex gap-4">
                            <button
                                className="bg-green-600 text-white px-6 py-3 rounded-lg w-full text-lg font-bold hover:bg-green-700 transition-all active:scale-95 shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                                onClick={crearTurno}
                                disabled={generando || !paciente.tipo_documento}
                            >
                                Generar turno
                            </button>
                            <button
                                className="bg-gray-500 text-white px-6 py-3 rounded-lg text-lg font-bold hover:bg-gray-600 transition-all active:scale-95 shadow-lg disabled:opacity-50"
                                onClick={resetFormulario}
                                disabled={generando}
                            >
                                Escanear de nuevo
                            </button>
                        </div>
                    </>
                )}
            </div>
        </div>
    );
}