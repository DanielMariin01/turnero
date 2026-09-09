import React, { useState, useRef, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import Swal from "sweetalert2";
import { connectQZ, isQZConnected } from "../qzConfig";

export default function UrgenciasPage() {
    const navigate = useNavigate();
    const procesandoRef = useRef(false);

    const [paciente, setPaciente] = useState({
        nombre: "",
        apellido: "",
        tipo_documento: "",
        numero_documento: "",
        fecha_nacimiento: "",
        sexo: "",
    });

    const inputActivo = useRef(null);
    const [scanBuffer, setScanBuffer] = useState('');
    const [mensajeEscaneo, setMensajeEscaneo] = useState('');
    const scanTimeoutRef = useRef(null);
    const [generando, setGenerando] = useState(false);

    // ============================================
    // CONTRATO / EPS
    // ============================================
    const [contratos, setContratos] = useState([]);
    const [busquedaContrato, setBusquedaContrato] = useState('');
    const [contratoSeleccionado, setContratoSeleccionado] = useState(null);

    useEffect(() => {
        fetch('/api/contratos')
            .then(res => res.json())
            .then(data => setContratos(data))
            .catch(err => console.error('Error cargando contratos:', err));
    }, []);

    useEffect(() => {
        connectQZ().catch(err => console.error("QZ no conectó al iniciar:", err));
    }, []);

    // ============================================
    // TIMER DE INACTIVIDAD (30 segundos)
    // ============================================
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

    // ============================================
    // DETECTOR DE ESCANEO DE CÓDIGO DE BARRAS
    // ============================================
    useEffect(() => {
        const handleScan = (e) => {
            const elementoActivo = document.activeElement;
            const esInputManual = elementoActivo.tagName === 'INPUT' ||
                elementoActivo.tagName === 'TEXTAREA' ||
                elementoActivo.tagName === 'SELECT';

            if (esInputManual && inputActivo.current) {
                return;
            }

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
                scanTimeoutRef.current = setTimeout(() => {
                    setScanBuffer('');
                }, 200);
            }
        };

        window.addEventListener('keydown', handleScan);
        return () => {
            window.removeEventListener('keydown', handleScan);
            clearTimeout(scanTimeoutRef.current);
        };
    }, [scanBuffer]);

    // ============================================
    // MOSTRAR MENSAJE TEMPORAL
    // ============================================
    const mostrarMensaje = (mensaje, tipo) => {
        setMensajeEscaneo({ texto: mensaje, tipo });
        setTimeout(() => setMensajeEscaneo(''), 4000);
    };

    // ============================================
    // SONIDO DE ÉXITO
    // ============================================
    const playSuccessSound = () => {
        try {
            const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBTGH0fPTgjMGHm7A7+OZURE=');
            audio.volume = 0.3;
            audio.play().catch(() => { });
        } catch (e) { }
    };

    // ============================================
    // PROCESAR CÉDULA COLOMBIANA + VERIFICAR PACIENTE
    // ============================================
    const procesarCedulaColombia = async (codigoCompleto) => {
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
                mostrarMensaje('⚠️ Formato de cédula no reconocido. Por favor use entrada manual.', 'warning');
                return;
            }

            let numeroDocumento, nombres, apellidos;
            let fechaNacimiento = '';
            let sexo = '';

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

                // Extraer fecha de nacimiento y sexo, según el formato de cédula (antigua vs nueva)
                if (partes.length >= 8) {
                    // Cédula ANTIGUA: sexo en [5], fecha DDMMYYYY en [6]
                    sexo = (partes[5]?.trim() || '').toUpperCase();
                    const fechaRaw = partes[6]?.trim() || '';
                    if (fechaRaw.length === 8) {
                        const dia = fechaRaw.substring(0, 2);
                        const mes = fechaRaw.substring(2, 4);
                        const anio = fechaRaw.substring(4, 8);
                        fechaNacimiento = `${anio}-${mes}-${dia}`;
                    }
                } else if (partes.length === 7) {
                    // Cédula NUEVA: fecha AAMMDD en [5], sexo en [6]
                    const fechaRaw = partes[5]?.trim() || '';
                    sexo = (partes[6]?.trim() || '').toUpperCase();
                    if (fechaRaw.length === 6) {
                        const aa = parseInt(fechaRaw.substring(0, 2), 10);
                        const mes = fechaRaw.substring(2, 4);
                        const dia = fechaRaw.substring(4, 6);
                        const anioActual = new Date().getFullYear();
                        const siglo2000 = 2000 + aa;
                        const anio = siglo2000 > anioActual ? 1900 + aa : siglo2000;
                        fechaNacimiento = `${anio}-${mes}-${dia}`;
                    }
                }
            }

            if (!numeroDocumento || !nombres || !apellidos) {
                mostrarMensaje('⚠️ Datos incompletos en la cédula.', 'warning');
                return;
            }

            try {
                const response = await fetch(`/api/pacientes/${numeroDocumento}`);
                if (response.ok) {
                    const data = await response.json();
                    setPaciente({
                        nombre: data.nombre,
                        apellido: data.apellido,
                        tipo_documento: data.tipo_documento,
                        numero_documento: data.numero_documento,
                        fecha_nacimiento: fechaNacimiento,
                        sexo: sexo,
                    });
                    mostrarMensaje('✅ Paciente encontrado, datos cargados', 'success');
                } else {
                    setPaciente({
                        nombre: nombres,
                        apellido: apellidos,
                        tipo_documento: '',
                        numero_documento: numeroDocumento,
                        fecha_nacimiento: fechaNacimiento,
                        sexo: sexo,
                    });
                    mostrarMensaje('✅ Cédula escaneada. Seleccione el tipo de documento.', 'success');
                }
            } catch (err) {
                setPaciente({
                    nombre: nombres,
                    apellido: apellidos,
                    tipo_documento: '',
                    numero_documento: numeroDocumento,
                    fecha_nacimiento: fechaNacimiento,
                    sexo: sexo,
                });
                mostrarMensaje('✅ Cédula escaneada correctamente', 'success');
            }

            playSuccessSound();
        } catch (error) {
            console.error('❌ Error procesando cédula:', error);
            mostrarMensaje('❌ Error al procesar la cédula. Intente nuevamente.', 'error');
        }
    };

    // ============================================
    // BUSCAR PACIENTE SI EXISTE EN LA BASE DE DATOS CLINICA
    // ============================================

    const buscarPacienteClinica = async (documento) => {
        if (!documento || documento.length < 6) return;

        try {
            const response = await fetch(`/api/clinica/pacientes/${documento}`);
            if (response.ok) {
                const data = await response.json();
                setPaciente(prev => ({
                    ...prev,
                    nombre: data.nombre.toUpperCase(),
                    apellido: data.apellido.toUpperCase(),
                    tipo_documento: data.tipo_documento,
                }));

                if (data.contrato) {
                    setContratoSeleccionado(data.contrato);
                    setBusquedaContrato(data.contrato.nombre);
                }

                mostrarMensaje('✅ Paciente encontrado en la clínica, datos cargados', 'success');
            }
        } catch (err) {
            console.error('Error consultando paciente en la clínica:', err);
        }
    };

    // ============================================
    // MANEJO DE CAMBIOS EN FORMULARIO
    // ============================================
    const handleChange = (campo, valor) => {
        setPaciente((prev) => ({
            ...prev,
            [campo]: valor,
        }));
    };

    // ============================================
    // GENERAR TURNO
    // ============================================
    const handleGuardar = async () => {
        if (!paciente.nombre.trim()) {
            Swal.fire({ title: "Campo requerido", text: "Por favor ingrese el nombre del paciente", icon: "warning", confirmButtonText: "Aceptar" });
            return;
        }
        if (!paciente.apellido.trim()) {
            Swal.fire({ title: "Campo requerido", text: "Por favor ingrese el apellido del paciente", icon: "warning", confirmButtonText: "Aceptar" });
            return;
        }
        if (!paciente.tipo_documento) {
            Swal.fire({ title: "Campo requerido", text: "Por favor seleccione el tipo de documento", icon: "warning", confirmButtonText: "Aceptar" });
            return;
        }
        if (!paciente.numero_documento.trim()) {
            Swal.fire({ title: "Campo requerido", text: "Por favor ingrese el número de documento", icon: "warning", confirmButtonText: "Aceptar" });
            return;
        }
        if (!paciente.fecha_nacimiento) {
            Swal.fire({ title: "Campo requerido", text: "Por favor ingrese la fecha de nacimiento", icon: "warning", confirmButtonText: "Aceptar" });
            return;
        }
        if (!paciente.sexo) {
            Swal.fire({ title: "Campo requerido", text: "Por favor seleccione el sexo", icon: "warning", confirmButtonText: "Aceptar" });
            return;
        }
        if (!contratoSeleccionado) {
            Swal.fire({ title: "Campo requerido", text: "Por favor busque y seleccione su EPS, SOAT o Particular", icon: "warning", confirmButtonText: "Aceptar" });
            return;
        }

        if (procesandoRef.current) return;
        procesandoRef.current = true;
        setGenerando(true);

        Swal.fire({
            title: "Generando turno...",
            text: "Por favor espere",
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => Swal.showLoading(),
        });

        let turnoCreado = null;

        try {
            const datosMayus = {
                nombre: paciente.nombre.toUpperCase(),
                apellido: paciente.apellido.toUpperCase(),
                tipo_documento: paciente.tipo_documento,
                numero_documento: paciente.numero_documento,
                fecha_nacimiento: paciente.fecha_nacimiento,
                sexo: paciente.sexo,
                contrato_nit: contratoSeleccionado.nit,
                contrato_nombre: contratoSeleccionado.nombre,
            };

            const response = await fetch("/api/turno/urgencias", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(datosMayus),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || "Error al generar el turno");
            }

            turnoCreado = data.turno;
        } catch (error) {
            console.error("Error creando turno:", error);
            Swal.fire({
                title: "Error",
                text: error.message || "No se pudo generar el turno.",
                icon: "error",
                confirmButtonText: "Cerrar",
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
                limpiarFormulario();
                navigate("/Urgencias");
                return;
            }
        }

        await Swal.fire({
            title: "¡Turno creado!",
            text: `Tu turno de urgencias fue registrado: ${turnoCreado?.numero_turno ?? ''}`,
            icon: "success",
            confirmButtonText: "Aceptar",
        });

        procesandoRef.current = false;
        setGenerando(false);
        limpiarFormulario();
        navigate("/Urgencias");
    };

    // ============================================
    // LIMPIAR FORMULARIO
    // ============================================
    const limpiarFormulario = () => {
        setPaciente({
            nombre: nombres,
            apellido: apellidos,
            tipo_documento: '',
            numero_documento: numeroDocumento,
            fecha_nacimiento: fechaNacimiento,
            sexo: sexo,
        });
        setMensajeEscaneo('');
        inputActivo.current = null;
        setBusquedaContrato('');
        setContratoSeleccionado(null);
    };

    // ============================================
    // TECLADO EN PANTALLA
    // ============================================
    const TecladoMovil = ({ onClickTecla, onBorrar }) => {
        const fila1 = "QWERTYUIOP".split("");
        const fila2 = "ASDFGHJKL".split("");
        const fila3 = "ZXCVBNM".split("");
        const numeros = "1234567890".split("");

        return (
            <div className="w-full bg-slate-900 p-3 rounded-t-2xl shadow-2xl">
                <div className="flex gap-1.5 mb-2">
                    {numeros.map((num) => (
                        <button
                            key={num}
                            onClick={() => onClickTecla(num)}
                            className="bg-[#587EAC] text-white p-2 rounded-lg text-lg font-bold hover:bg-gray-600 flex-1 h-12 transition-all active:scale-95"
                        >
                            {num}
                        </button>
                    ))}
                </div>

                <div className="flex gap-1.5 mb-2">
                    {fila1.map((letra) => (
                        <button
                            key={letra}
                            onClick={() => onClickTecla(letra)}
                            className="bg-white text-gray-800 p-2 rounded-lg text-lg font-bold hover:bg-gray-100 flex-1 h-12 transition-all active:scale-95"
                        >
                            {letra}
                        </button>
                    ))}
                </div>

                <div className="flex gap-1.5 mb-2">
                    {fila2.map((letra) => (
                        <button
                            key={letra}
                            onClick={() => onClickTecla(letra)}
                            className="bg-white text-gray-800 p-2 rounded-lg text-lg font-bold hover:bg-gray-100 flex-1 h-12 transition-all active:scale-95"
                        >
                            {letra}
                        </button>
                    ))}
                </div>

                <div className="flex gap-1.5 mb-2">
                    <button
                        onClick={onBorrar}
                        className="bg-[#686868] text-white p-2 rounded-lg font-bold hover:bg-red-700 flex-1 h-12 text-sm transition-all active:scale-95"
                    >
                        ← DEL
                    </button>

                    {fila3.map((letra) => (
                        <button
                            key={letra}
                            onClick={() => onClickTecla(letra)}
                            className="bg-white text-gray-800 p-2 rounded-lg text-lg font-bold hover:bg-gray-100 flex-1 h-12 transition-all active:scale-95"
                        >
                            {letra}
                        </button>
                    ))}

                    <button
                        onClick={onBorrar}
                        className="bg-[#686868] text-white p-2 rounded-lg font-bold hover:bg-red-700 flex-1 h-12 text-sm transition-all active:scale-95"
                    >
                        DEL →
                    </button>
                </div>

                <div className="flex gap-1.5">
                    <button
                        onClick={() => onClickTecla(" ")}
                        className="bg-[#00A09B] text-white p-2 rounded-lg font-bold hover:bg-[#028580] flex-1 h-12 text-base transition-all active:scale-95"
                    >
                        ESPACIO
                    </button>
                </div>
            </div>
        );
    };

    const escribirTecla = (tecla) => {
        if (!inputActivo.current) return;
        const campo = inputActivo.current;

        if (campo.name === 'busqueda_contrato') {
            setBusquedaContrato(prev => prev + tecla);
            setContratoSeleccionado(null);
            return;
        }

        handleChange(campo.name, paciente[campo.name] + tecla);
    };

    const borrarTecla = () => {
        if (!inputActivo.current) return;
        const campo = inputActivo.current;

        if (campo.name === 'busqueda_contrato') {
            setBusquedaContrato(prev => prev.slice(0, -1));
            return;
        }

        handleChange(campo.name, paciente[campo.name].slice(0, -1));
    };

    // ============================================
    // RENDER
    // ============================================
    return (
        <div className="flex flex-col h-screen w-[1000px] mx-auto bg-gradient-to-br from-blue-50 bg-[#5B6BB1]">
            <div className="flex-1 flex items-center justify-center p-4 overflow-auto">
                <div className="w-full max-w-5xl bg-white shadow-2xl rounded-2xl p-6">
                    <h2 className="text-3xl font-bold mb-4 text-center text-indigo-700">
                        Turno de Urgencias
                    </h2>

                    {mensajeEscaneo && (
                        <div className={`mb-4 p-3 rounded-lg border-l-4 transition-all ${mensajeEscaneo.tipo === 'success'
                            ? 'bg-green-50 border-green-500 text-green-800'
                            : mensajeEscaneo.tipo === 'warning'
                                ? 'bg-yellow-50 border-yellow-500 text-yellow-800'
                                : 'bg-red-50 border-red-500 text-red-800'
                            }`}>
                            <p className="font-semibold flex items-center gap-2">
                                {mensajeEscaneo.tipo === 'success' && '✅'}
                                {mensajeEscaneo.tipo === 'warning' && '⚠️'}
                                {mensajeEscaneo.tipo === 'error' && '❌'}
                                {mensajeEscaneo.texto}
                            </p>
                        </div>
                    )}

                    <div className="grid grid-cols-2 gap-4 mb-5">
                        <div>
                            <label className="block text-lg font-semibold text-gray-700 mb-1">
                                Ingrese su Nombre
                            </label>
                            <input
                                type="text"
                                name="nombre"
                                placeholder="Nombre"
                                className="border-2 border-gray-300 p-3 rounded-lg w-full text-base focus:border-indigo-500 focus:outline-none transition-all"
                                value={paciente.nombre}
                                onFocus={(e) => (inputActivo.current = e.target)}
                                onChange={(e) => handleChange("nombre", e.target.value)}
                            />
                        </div>

                        <div>
                            <label className="block text-lg font-semibold text-gray-700 mb-1">
                                Ingrese su Apellido
                            </label>
                            <input
                                type="text"
                                name="apellido"
                                placeholder="Apellido"
                                className="border-2 border-gray-300 p-3 rounded-lg w-full text-base focus:border-indigo-500 focus:outline-none transition-all"
                                value={paciente.apellido}
                                onFocus={(e) => (inputActivo.current = e.target)}
                                onChange={(e) => handleChange("apellido", e.target.value)}
                            />
                        </div>
                        <div>
                            <label className="block text-lg font-semibold text-gray-700 mb-1">
                                Seleccione su Tipo de Documento
                            </label>
                            <select
                                name="tipo_documento"
                                className="border-2 border-gray-300 p-3 rounded-lg w-full text-base focus:border-indigo-500 focus:outline-none transition-all"
                                value={paciente.tipo_documento}
                                onFocus={() => (inputActivo.current = null)}
                                onChange={(e) => handleChange("tipo_documento", e.target.value)}
                            >
                                <option value="">Tipo de documento</option>
                                <option value="CC">Cédula de ciudadanía</option>
                                <option value="TI">Tarjeta de identidad</option>
                                <option value="CE">Cédula de extranjería</option>
                                <option value="PA">Pasaporte</option>
                                <option value="RC">Registro civil de nacimiento</option>
                                <option value="AS">Adulto sin identificación</option>
                                <option value="CD">Carné diplomático</option>
                                <option value="CN">Certificado de nacido vivo</option>
                                <option value="DE">Documento extranjero</option>
                                <option value="MS">Menor sin identificación</option>
                                <option value="NIT">NIT</option>
                                <option value="PE">Permiso especial de permanencia</option>
                                <option value="PT">Permiso por protección temporal</option>
                                <option value="SC">Salvoconducto de permanencia</option>
                                <option value="SI">Sin identificación</option>
                                <option value="TE">Tarjeta de extranjería</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-lg font-semibold text-gray-700 mb-1">
                                Digite su Número de Documento
                            </label>
                            <input
                                type="text"
                                name="numero_documento"
                                placeholder="Número de documento"
                                className="border-2 border-gray-300 p-3 rounded-lg w-full text-base focus:border-indigo-500 focus:outline-none transition-all"
                                value={paciente.numero_documento}
                                onFocus={(e) => (inputActivo.current = e.target)}
                                onChange={(e) =>
                                    handleChange("numero_documento", e.target.value.replace(/\D/g, ""))
                                }
                                onBlur={(e) => buscarPacienteClinica(e.target.value)}
                            />
                        </div>
                        <div>
                            <label className="block text-lg font-semibold text-gray-700 mb-1">
                                Seleccione su Fecha de Nacimiento
                            </label>
                            <input
                                type="date"
                                name="fecha_nacimiento"
                                placeholder="Fecha de nacimiento"
                                className="border-2 border-gray-300 p-3 rounded-lg w-full text-base focus:border-indigo-500 focus:outline-none transition-all"
                                value={paciente.fecha_nacimiento}
                                onFocus={() => (inputActivo.current = null)}
                                onChange={(e) => handleChange("fecha_nacimiento", e.target.value)}
                                max={new Date().toISOString().split('T')[0]}
                            />
                        </div>
                        <div>
                            <label className="block text-lg font-semibold text-gray-700 mb-1">
                                Seleccione su Sexo
                            </label>
                            <select
                                name="sexo"
                                className="border-2 border-gray-300 p-3 rounded-lg w-full text-base focus:border-indigo-500 focus:outline-none transition-all"
                                value={paciente.sexo}
                                onFocus={() => (inputActivo.current = null)}
                                onChange={(e) => handleChange("sexo", e.target.value)}
                            >
                                <option value="">Sexo</option>
                                <option value="M">Masculino</option>
                                <option value="F">Femenino</option>
                            </select>
                        </div>
                        <div className="col-span-2">
                            <label className="block text-lg font-semibold text-gray-700 mb-1">
                                ¿Con qué EPS o seguro viene?
                            </label>
                            <div className="relative">
                                <input
                                    type="text"
                                    name="busqueda_contrato"
                                    placeholder="Seleccionao o Busca  EPS, SOAT o Particular..."
                                    className="border-2 border-gray-300 p-3 rounded-lg w-full text-base focus:border-indigo-500 focus:outline-none transition-all"
                                    value={busquedaContrato}
                                    onFocus={(e) => (inputActivo.current = e.target)}
                                    onChange={(e) => {
                                        setBusquedaContrato(e.target.value);
                                        setContratoSeleccionado(null);
                                    }}
                                />
                                {contratoSeleccionado && (
                                    <p className="text-sm text-green-700 mt-1 font-semibold">
                                        ✅ Seleccionado: {contratoSeleccionado.nombre}
                                    </p>
                                )}
                                {busquedaContrato && !contratoSeleccionado && (
                                    <div className="absolute z-10 w-full bg-white border-2 border-gray-200 rounded-lg mt-1 max-h-48 overflow-y-auto shadow-lg">
                                        {contratos
                                            .filter(c => c.nombre.toLowerCase().includes(busquedaContrato.toLowerCase()))
                                            .slice(0, 8)
                                            .map(c => (
                                                <div
                                                    key={c.nit}
                                                    className="p-3 hover:bg-indigo-50 cursor-pointer border-b border-gray-100 text-sm"
                                                    onClick={() => {
                                                        setContratoSeleccionado(c);
                                                        setBusquedaContrato(c.nombre);
                                                    }}
                                                >
                                                    {c.nombre}
                                                </div>
                                            ))}
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="flex gap-4 mt-5">
                        <button
                            className="bg-[#5B6BB1] text-white px-6 py-3 rounded-lg w-full text-lg font-bold bg-[#5564A8] transition-all active:scale-95 shadow-lg flex items-center justify-center gap-2 disabled:opacity-50"
                            onClick={handleGuardar}
                            disabled={generando}
                        >
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Generar Turno
                        </button>

                        <button
                            className="bg-gray-500 text-white px-6 py-3 rounded-lg text-lg font-bold hover:bg-gray-600 transition-all active:scale-95 shadow-lg flex items-center justify-center gap-2"
                            onClick={limpiarFormulario}
                            disabled={generando}
                        >
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Limpiar
                        </button>
                    </div>
                </div>
            </div>

            <TecladoMovil onClickTecla={escribirTecla} onBorrar={borrarTecla} />
        </div>
    );
}