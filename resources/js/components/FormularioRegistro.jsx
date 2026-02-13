import React, { useState, useRef, useEffect } from "react";
import { useNavigate, useLocation } from "react-router-dom";
import Swal from "sweetalert2";

export default function FormularioRegistro() {
  const navigate = useNavigate();
  const location = useLocation();

  // Estado del paciente
  const [paciente, setPaciente] = useState({
    nombre: "",
    apellido: "",
    tipo_documento: "",
    numero_documento: "",
  });

  // Referencias y estados para el escáner
  const inputActivo = useRef(null);
  const [scanBuffer, setScanBuffer] = useState('');
  const [mensajeEscaneo, setMensajeEscaneo] = useState('');
  const scanTimeoutRef = useRef(null);

  // ============================================
  // TIMER DE INACTIVIDAD (20 segundos)
  // ============================================
  useEffect(() => {
    let timer = setTimeout(() => {
      navigate("/");
    }, 20000);

    const resetTimer = () => {
      clearTimeout(timer);
      timer = setTimeout(() => {
        navigate("/");
      }, 20000);
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
      // IMPORTANTE: Solo procesar si NO hay un input del teclado en pantalla activo
      const elementoActivo = document.activeElement;
      const esInputManual = elementoActivo.tagName === 'INPUT' ||
        elementoActivo.tagName === 'TEXTAREA' ||
        elementoActivo.tagName === 'SELECT';

      // Si hay un input activo del teclado en pantalla, permitir escritura normal
      if (esInputManual && inputActivo.current) {
        return;
      }

      // Detectar Enter (fin de escaneo)
      // Detectar Enter (fin de escaneo)
      if (e.key === 'Enter') {
        if (scanBuffer.length > 5) {
          e.preventDefault();
          console.log('🔍 ENTER detectado - Procesando buffer:', scanBuffer);
          procesarCedulaColombia(scanBuffer);
          setScanBuffer('');
        }
        return;
      }

      // ⭐ IGNORAR teclas modificadoras
      if (e.key === 'Shift' || e.key === 'Control' || e.key === 'Alt' || e.key === 'Meta') {
        return; // NO capturar Shift, Control, Alt, Meta
      }

      // Acumular TODOS los caracteres del escaneo (incluyendo espacios, tabs, etc)
      if (!e.ctrlKey && !e.altKey && !e.metaKey) {
        let char = e.key;

        // Casos especiales
        if (e.key === 'Tab') {
          char = '\t';
          e.preventDefault();
        } else if (e.key === ' ') {
          char = ' ';
          e.preventDefault();
        } else if (e.key.length === 1) {
          e.preventDefault();
        }

        console.log(`📝 Capturando: "${char}" (código: ${char.charCodeAt(0)})`);
        setScanBuffer(prev => prev + char);

        // Limpiar buffer después de 200ms de inactividad (aumentado para dar más tiempo)
        clearTimeout(scanTimeoutRef.current);
        scanTimeoutRef.current = setTimeout(() => {
          if (scanBuffer.length > 0 && scanBuffer.length < 10) {
            console.log('⏱️ Timeout - Buffer limpiado:', scanBuffer);
          }
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
  // PROCESAR CÉDULA COLOMBIANA
  // ============================================
  const procesarCedulaColombia = (codigoCompleto) => {
    console.log('========== INICIO ESCANEO ==========');
    console.log('Código escaneado (raw):', codigoCompleto);
    console.log('Longitud:', codigoCompleto.length);
    console.log('Contiene TAB (\\t):', codigoCompleto.includes('\t'));
    console.log('Contiene $:', codigoCompleto.includes('$'));
    console.log('Contiene espacio:', codigoCompleto.includes(' '));
    console.log('Contiene espacios múltiples:', codigoCompleto.includes('  '));
    console.log('Contiene |:', codigoCompleto.includes('|'));
    console.log('Contiene ^:', codigoCompleto.includes('^'));

    // Mostrar cada carácter y su código
    console.log('Caracteres detallados:');
    for (let i = 0; i < Math.min(codigoCompleto.length, 150); i++) {
      const char = codigoCompleto[i];
      const code = codigoCompleto.charCodeAt(i);
      const desc = code === 9 ? '(TAB)' : code === 32 ? '(SPACE)' : '';
      console.log(`  [${i}] "${char}" = ${code} ${desc}`);
    }
    console.log('====================================');

    try {
      let partes = [];
      let formatoDetectado = '';
      let separador = '';

      // Detectar el separador usado
      if (codigoCompleto.includes('\t')) {
        separador = '\t';
        formatoDetectado = 'TAB';
        partes = codigoCompleto.split('\t');
      } else if (codigoCompleto.includes('  ')) {
        // Espacios múltiples (2 o más espacios seguidos)
        separador = /\s{2,}/; // Regex: 2 o más espacios
        formatoDetectado = 'ESPACIOS MÚLTIPLES';
        partes = codigoCompleto.split(/\s{2,}/).map(p => p.trim()).filter(Boolean);
      } else if (codigoCompleto.includes('$')) {
        separador = '$';
        formatoDetectado = '$';
        partes = codigoCompleto.split('$');
      } else if (codigoCompleto.includes('|')) {
        separador = '|';
        formatoDetectado = '|';
        partes = codigoCompleto.split('|');
      } else if (codigoCompleto.includes('^')) {
        separador = '^';
        formatoDetectado = '^';
        partes = codigoCompleto.split('^');
      } else if (codigoCompleto.includes(' ')) {
        // Espacios simples como último recurso
        separador = ' ';
        formatoDetectado = 'ESPACIO SIMPLE';
        partes = codigoCompleto.split(' ').filter(Boolean);
      } else {
        // Sin separador detectado - mostrar info de debug
        console.error('❌ NO SE DETECTÓ NINGÚN SEPARADOR');
        console.error('Esto puede pasar si:');
        console.error('1. El escáner no está enviando separadores');
        console.error('2. Los separadores se están perdiendo en el camino');
        console.error('3. El formato es completamente diferente');
        mostrarMensaje('⚠️ Formato de cédula no reconocido. Por favor use entrada manual.', 'warning');
        return;
      }

      console.log(`✓ Separador detectado: "${formatoDetectado}"`);
      console.log('✓ Número de partes:', partes.length);
      console.log('✓ Partes:', partes);

      // Procesar según el formato
      if (formatoDetectado === '$') {
        // FORMATO CARNET INSTITUCIONAL: 0$DOC$AP1$AP2$NOM1$NOM2$...
        if (partes.length < 6) {
          console.warn('Partes insuficientes para formato $');
          mostrarMensaje('⚠️ Código incompleto. Intente nuevamente.', 'warning');
          return;
        }

        const tipoInicial = partes[0]?.trim() || '';
        const numeroDocumento = partes[1]?.trim() || '';
        const apellido1 = partes[2]?.trim() || '';
        const apellido2 = partes[3]?.trim() || '';
        const nombre1 = partes[4]?.trim() || '';
        const nombre2 = partes[5]?.trim() || '';

        const apellidos = [apellido1, apellido2].filter(Boolean).join(' ');
        const nombres = [nombre1, nombre2].filter(Boolean).join(' ');

        console.log('✓ Datos extraídos (formato $):', { numeroDocumento, apellidos, nombres });

        if (!numeroDocumento || !nombres || !apellidos) {
          mostrarMensaje('⚠️ Datos incompletos.', 'warning');
          return;
        }

        setPaciente({
          nombre: nombres,
          apellido: apellidos,
          tipo_documento: 'CC',
          numero_documento: numeroDocumento,
        });

        mostrarMensaje(`✅ Carnet escaneado correctamente`, 'success');
        playSuccessSound();

      } else {
        // FORMATO CÉDULA COLOMBIANA: DOC[SEP]AP1[SEP]AP2[SEP]NOM1[SEP]NOM2[SEP]...
        // Formato esperado: DOCUMENTO APELLIDO1 APELLIDO2 NOMBRE1 NOMBRE2 SEXO? FECHA? TIPOSANGRE?
        if (partes.length < 5) {
          console.warn('Partes insuficientes para formato cédula');
          console.warn('Se esperaban al menos 5 partes, se recibieron:', partes.length);
          mostrarMensaje('⚠️ Código de cédula incompleto. Intente nuevamente.', 'warning');
          return;
        }

        const numeroDocumento = partes[0]?.trim() || '';
        const apellido1 = partes[1]?.trim() || '';
        const apellido2 = partes[2]?.trim() || '';
        const nombre1 = partes[3]?.trim() || '';
        const nombre2 = partes[4]?.trim() || '';
        // partes[5] = sexo o fecha
        // partes[6] = fecha o tipo sangre
        // partes[7] = tipo sangre

        const apellidos = [apellido1, apellido2].filter(Boolean).join(' ');
        const nombres = [nombre1, nombre2].filter(Boolean).join(' ');

        console.log('✓ Datos extraídos (cédula):', { numeroDocumento, apellidos, nombres });

        if (!numeroDocumento || !nombres || !apellidos) {
          console.warn('Validación fallida:', { numeroDocumento, apellidos, nombres });
          mostrarMensaje('⚠️ Datos incompletos en la cédula.', 'warning');
          return;
        }

        setPaciente({
          nombre: nombres,
          apellido: apellidos,
          tipo_documento: 'CC',
          numero_documento: numeroDocumento,
        });

        console.log('✅ Formulario actualizado exitosamente');
        mostrarMensaje(`✅ Cédula escaneada correctamente`, 'success');
        playSuccessSound();
      }

    } catch (error) {
      console.error('❌ Error procesando cédula:', error);
      console.error('Stack:', error.stack);
      mostrarMensaje('❌ Error al procesar la cédula. Intente nuevamente.', 'error');
    }
  };

  // ============================================
  // MOSTRAR MENSAJE TEMPORAL
  // ============================================
  const mostrarMensaje = (mensaje, tipo) => {
    setMensajeEscaneo({ texto: mensaje, tipo });
    setTimeout(() => setMensajeEscaneo(''), 4000);
  };

  // ============================================
  // SONIDO DE ÉXITO (OPCIONAL)
  // ============================================
  const playSuccessSound = () => {
    try {
      const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBTGH0fPTgjMGHm7A7+OZURE=');
      audio.volume = 0.3;
      audio.play().catch(() => { }); // Ignorar errores si el navegador bloquea el audio
    } catch (e) {
      // Ignorar errores de audio
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
  // GUARDAR PACIENTE
  // ============================================
  const handleGuardar = async () => {
    // Validaciones básicas
    if (!paciente.nombre.trim()) {
      Swal.fire({
        title: "Campo requerido",
        text: "Por favor ingrese el nombre del paciente",
        icon: "warning",
        confirmButtonText: "Aceptar",
      });
      return;
    }

    if (!paciente.apellido.trim()) {
      Swal.fire({
        title: "Campo requerido",
        text: "Por favor ingrese el apellido del paciente",
        icon: "warning",
        confirmButtonText: "Aceptar",
      });
      return;
    }

    if (!paciente.tipo_documento) {
      Swal.fire({
        title: "Campo requerido",
        text: "Por favor seleccione el tipo de documento",
        icon: "warning",
        confirmButtonText: "Aceptar",
      });
      return;
    }

    if (!paciente.numero_documento.trim()) {
      Swal.fire({
        title: "Campo requerido",
        text: "Por favor ingrese el número de documento",
        icon: "warning",
        confirmButtonText: "Aceptar",
      });
      return;
    }

    try {
      Swal.fire({
        title: "Creando registro...",
        text: "Por favor espere",
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
          Swal.showLoading();
        },
      });

      const datosMayus = {
        nombre: paciente.nombre.toUpperCase(),
        apellido: paciente.apellido.toUpperCase(),
        tipo_documento: paciente.tipo_documento,
        numero_documento: paciente.numero_documento,
      };

      const response = await fetch("/api/pacientes", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(datosMayus),
      });

      const data = await response.json();

      if (!response.ok) {
        // Manejar error de documento duplicado
        if (response.status === 422 && data.errors?.numero_documento) {
          throw new Error("Este número de documento ya está registrado");
        }
        throw new Error(data.message || "Error al registrar");
      }

      Swal.fire({
        title: "¡Registro exitoso!",
        text: "El paciente fue registrado correctamente.",
        icon: "success",
        confirmButtonText: "Aceptar",
      }).then(() => navigate("/"));
    } catch (error) {
      Swal.fire({
        title: "Error",
        text: error.message || "Hubo un problema al registrar el paciente.",
        icon: "error",
        confirmButtonText: "Cerrar",
      });
    }
  };

  // ============================================
  // LIMPIAR FORMULARIO
  // ============================================
  const limpiarFormulario = () => {
    setPaciente({
      nombre: "",
      apellido: "",
      tipo_documento: "",
      numero_documento: "",
    });
    setMensajeEscaneo('');
    inputActivo.current = null;
  };

  // ============================================
  // TECLADO OPTIMIZADO PARA 24"
  // ============================================
  const TecladoMovil = ({ onClickTecla, onBorrar }) => {
    const fila1 = "QWERTYUIOP".split("");
    const fila2 = "ASDFGHJKL".split("");
    const fila3 = "ZXCVBNM".split("");
    const numeros = "1234567890".split("");

    return (
      <div className="w-full bg-gray-800 p-3 rounded-t-2xl shadow-2xl">
        {/* Fila de números */}
        <div className="flex justify-center gap-1.5 mb-2">
          {numeros.map((num) => (
            <button
              key={num}
              onClick={() => onClickTecla(num)}
              className="bg-gray-700 text-white p-2 rounded-lg text-lg font-bold hover:bg-gray-600 w-16 h-12 transition-all active:scale-95"
            >
              {num}
            </button>
          ))}
        </div>

        {/* Primera fila - QWERTY */}
        <div className="flex justify-center gap-1.5 mb-2">
          {fila1.map((letra) => (
            <button
              key={letra}
              onClick={() => onClickTecla(letra)}
              className="bg-white text-gray-800 p-2 rounded-lg text-lg font-bold hover:bg-gray-100 w-16 h-12 transition-all active:scale-95"
            >
              {letra}
            </button>
          ))}
        </div>

        {/* Segunda fila - ASDF */}
        <div className="flex justify-center gap-1.5 mb-2">
          {fila2.map((letra) => (
            <button
              key={letra}
              onClick={() => onClickTecla(letra)}
              className="bg-white text-gray-800 p-2 rounded-lg text-lg font-bold hover:bg-gray-100 w-16 h-12 transition-all active:scale-95"
            >
              {letra}
            </button>
          ))}
        </div>

        {/* Tercera fila - ZXCV + Borrar */}
        <div className="flex justify-center gap-1.5 mb-2">
          <button
            onClick={onBorrar}
            className="bg-red-600 text-white p-2 rounded-lg font-bold hover:bg-red-700 w-20 h-12 text-sm transition-all active:scale-95"
          >
            ← DEL
          </button>

          {fila3.map((letra) => (
            <button
              key={letra}
              onClick={() => onClickTecla(letra)}
              className="bg-white text-gray-800 p-2 rounded-lg text-lg font-bold hover:bg-gray-100 w-16 h-12 transition-all active:scale-95"
            >
              {letra}
            </button>
          ))}

          <button
            onClick={onBorrar}
            className="bg-red-600 text-white p-2 rounded-lg font-bold hover:bg-red-700 w-20 h-12 text-sm transition-all active:scale-95"
          >
            DEL →
          </button>
        </div>

        {/* Barra espaciadora */}
        <div className="flex justify-center gap-1.5">
          <button
            onClick={() => onClickTecla(" ")}
            className="bg-blue-500 text-white p-2 rounded-lg font-bold hover:bg-blue-600 flex-1 h-12 text-base transition-all active:scale-95"
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
    handleChange(campo.name, paciente[campo.name] + tecla);
  };

  const borrarTecla = () => {
    if (!inputActivo.current) return;
    const campo = inputActivo.current;
    handleChange(campo.name, paciente[campo.name].slice(0, -1));
  };

  // ============================================
  // RENDER
  // ============================================
  return (
    <div className="flex flex-col h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
      {/* FORMULARIO - PARTE SUPERIOR */}
      <div className="flex-1 flex items-center justify-center p-4 overflow-auto">
        <div className="w-full max-w-5xl bg-white shadow-2xl rounded-2xl p-6">
          <h2 className="text-3xl font-bold mb-4 text-center text-indigo-700">
            Registro de Paciente
          </h2>

          {/* INSTRUCCIONES DE ESCANEO */}
          <div className="mb-4 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-l-4 border-indigo-500 rounded-lg">
            <div className="flex items-start gap-3">
              <svg className="w-6 h-6 text-indigo-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
              </svg>
              <div className="flex-1">
                <p className="font-semibold text-indigo-800 text-lg mb-1">📱 Escaneo rápido de cédula</p>
                <p className="text-indigo-700 text-sm">
                  Escanee el código de barras del <strong>reverso de la cédula</strong> para completar automáticamente los datos
                </p>
              </div>
            </div>
          </div>

          {/* MENSAJE DE ESTADO DE ESCANEO */}
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

          {/* CAMPOS EN HORIZONTAL */}
          <div className="grid grid-cols-2 gap-4 mb-5">
            <input
              type="text"
              name="nombre"
              placeholder="Nombre"
              className="border-2 border-gray-300 p-3 rounded-lg w-full text-base focus:border-indigo-500 focus:outline-none transition-all"
              value={paciente.nombre}
              onFocus={(e) => (inputActivo.current = e.target)}
              onChange={(e) => handleChange("nombre", e.target.value)}
            />

            <input
              type="text"
              name="apellido"
              placeholder="Apellido"
              className="border-2 border-gray-300 p-3 rounded-lg w-full text-base focus:border-indigo-500 focus:outline-none transition-all"
              value={paciente.apellido}
              onFocus={(e) => (inputActivo.current = e.target)}
              onChange={(e) => handleChange("apellido", e.target.value)}
            />

            <select
              name="tipo_documento"
              className="border-2 border-gray-300 p-3 rounded-lg w-full text-base focus:border-indigo-500 focus:outline-none transition-all"
              value={paciente.tipo_documento}
              onFocus={(e) => (inputActivo.current = null)}
              onChange={(e) => handleChange("tipo_documento", e.target.value)}
            >
              <option value="">Tipo de documento</option>
              <option value="CC">Cédula de ciudadanía</option>
              <option value="TI">Tarjeta de identidad</option>
              <option value="CE">Cédula de extranjería</option>
              <option value="PA">Pasaporte</option>
              <option value="RC">Registro Civil</option>
            </select>

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
            />
          </div>

          {/* BOTONES DE ACCIÓN */}
          <div className="flex gap-4 mt-5">
            <button
              className="bg-green-600 text-white px-6 py-3 rounded-lg w-full text-lg font-bold hover:bg-green-700 transition-all active:scale-95 shadow-lg flex items-center justify-center gap-2"
              onClick={handleGuardar}
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
              </svg>
              Guardar Registro
            </button>

            <button
              className="bg-gray-500 text-white px-6 py-3 rounded-lg text-lg font-bold hover:bg-gray-600 transition-all active:scale-95 shadow-lg flex items-center justify-center gap-2"
              onClick={limpiarFormulario}
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
              Limpiar
            </button>

            <button
              className="bg-red-600 text-white px-6 py-3 rounded-lg w-full text-lg font-bold hover:bg-red-700 transition-all active:scale-95 shadow-lg flex items-center justify-center gap-2"
              onClick={() => navigate(-1)}
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              Cancelar
            </button>
          </div>
        </div>
      </div>

      {/* TECLADO - PARTE INFERIOR */}
      <TecladoMovil onClickTecla={escribirTecla} onBorrar={borrarTecla} />
    </div>
  );
}

