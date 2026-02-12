import React, { useState, useRef, useEffect } from "react";
import { useNavigate, useLocation } from "react-router-dom";
import Swal from "sweetalert2";

export default function FormularioRegistro() {
  const navigate = useNavigate();
  const location = useLocation();

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

  const [paciente, setPaciente] = useState({
    nombre: "",
    apellido: "",
    tipo_documento: "",
    numero_documento: "",
  });

  const inputActivo = useRef(null);

  const handleChange = (campo, valor) => {
    setPaciente((prev) => ({
      ...prev,
      [campo]: valor,
    }));
  };

  const handleGuardar = async () => {
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

      if (!response.ok) throw new Error("Error al registrar");

      Swal.fire({
        title: "¡Registro exitoso!",
        text: "El paciente fue registrado correctamente.",
        icon: "success",
        confirmButtonText: "Aceptar",
      }).then(() => navigate("/"));
    } catch (error) {
      Swal.fire({
        title: "Error",
        text: "Hubo un problema al registrar el paciente.",
        icon: "error",
        confirmButtonText: "Cerrar",
      });
    }
  };

  // --------------------------
  // TECLADO OPTIMIZADO PARA 24"
  // --------------------------
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

  return (
    <div className="flex flex-col h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
      {/* FORMULARIO - PARTE SUPERIOR */}
      <div className="flex-1 flex items-center justify-center p-4 overflow-auto">
        <div className="w-full max-w-5xl bg-white shadow-2xl rounded-2xl p-6">
          <h2 className="text-3xl font-bold mb-6 text-center text-indigo-700">
            Registro de Paciente
          </h2>

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
              className="bg-green-600 text-white px-6 py-3 rounded-lg w-full text-lg font-bold hover:bg-green-700 transition-all active:scale-95 shadow-lg"
              onClick={handleGuardar}
            >
              Guardar Registro
            </button>

            <button
              className="bg-red-600 text-white px-6 py-3 rounded-lg w-full text-lg font-bold hover:bg-red-700 transition-all active:scale-95 shadow-lg"
              onClick={() => navigate(-1)}
            >
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