import React, { useState, useRef } from "react";
import { useNavigate } from "react-router-dom";
import Swal from "sweetalert2";

export default function FormularioRegistro() {
  const navigate = useNavigate();

  const [paciente, setPaciente] = useState({
    nombre: "",
    apellido: "",
    tipo_documento: "",
    numero_documento: "",
  });

  // Para saber en cuál input estamos escribiendo
  const inputActivo = useRef(null);

  const handleChange = (campo, valor) => {
    setPaciente((prev) => ({
      ...prev,
      [campo]: valor,
    }));
  };

  const handleGuardar = async () => {
    try {
      const response = await fetch("http://127.0.0.1:8000/api/pacientes", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(paciente),
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
  // TECLADO COMPLETO
  // --------------------------
  const TecladoCompleto = ({ onClickTecla, onBorrar }) => {
    const letras = "ABCDEFGHIJKLMNOPQRSTUVWXYZ".split("");
    const numeros = "1234567890".split("");

    return (
      <div className="grid grid-cols-6 gap-3 p-5 bg-gray-100 rounded-xl shadow-lg w-[350px] h-fit">

        {/* Números */}
        {numeros.map((num) => (
          <button
            key={num}
            onClick={() => onClickTecla(num)}
            className="bg-gray-300 p-4 rounded-xl text-xl font-bold hover:bg-gray-400"
          >
            {num}
          </button>
        ))}

        {/* Letras */}
        {letras.map((letra) => (
          <button
            key={letra}
            onClick={() => onClickTecla(letra)}
            className="bg-gray-200 p-4 rounded-xl text-lg font-semibold hover:bg-gray-300"
          >
            {letra}
          </button>
        ))}

        {/* Espacio */}
        <button
          onClick={() => onClickTecla(" ")}
          className="col-span-3 bg-blue-400 text-white p-4 rounded-xl font-bold hover:bg-blue-500"
        >
          Espacio
        </button>

        {/* Borrar */}
        <button
          onClick={onBorrar}
          className="col-span-3 bg-red-500 text-white p-4 rounded-xl font-bold hover:bg-red-600"
        >
          Borrar
        </button>
      </div>
    );
  };

  // Función cuando se presiona una tecla del teclado virtual
  const escribirTecla = (tecla) => {
    if (!inputActivo.current) return;

    const campo = inputActivo.current;

    handleChange(
      campo.name,
      paciente[campo.name] + tecla
    );
  };

  const borrarTecla = () => {
    if (!inputActivo.current) return;
    const campo = inputActivo.current;

    handleChange(
      campo.name,
      paciente[campo.name].slice(0, -1)
    );
  };

  return (
    <div className="flex justify-center gap-10 mt-10">

      {/* IZQUIERDA → TECLADO */}
      <TecladoCompleto onClickTecla={escribirTecla} onBorrar={borrarTecla} />

      {/* DERECHA → FORMULARIO */}
      <div className="w-[500px] bg-white shadow-lg rounded-2xl p-10">
        <h2 className="text-3xl font-bold mb-8 text-center text-color-700">
          Registro de Paciente
        </h2>

        <input
          type="text"
          name="nombre"
          placeholder="Nombre"
          className="border p-4 rounded-xl w-full mb-4"
          value={paciente.nombre}
          onFocus={(e) => (inputActivo.current = e.target)}
          onChange={(e) => handleChange("nombre", e.target.value)}
        />

        <input
          type="text"
          name="apellido"
          placeholder="Apellido"
          className="border p-4 rounded-xl w-full mb-4"
          value={paciente.apellido}
          onFocus={(e) => (inputActivo.current = e.target)}
          onChange={(e) => handleChange("apellido", e.target.value)}
        />

        <select
          name="tipo_documento"
          className="border p-4 rounded-xl w-full mb-4"
          value={paciente.tipo_documento}
          onFocus={(e) => (inputActivo.current = null)} // No se llena con teclado
          onChange={(e) => handleChange("tipo_documento", e.target.value)}
        >
          <option value="">Tipo de documento</option>
          <option value="CC">Cédula de ciudadanía</option>
          <option value="TI">Tarjeta de identidad</option>
          <option value="CE">Cédula de extranjería</option>
          <option value="PA">Pasaporte</option>
        </select>

        <input
          type="text"
          name="numero_documento"
          placeholder="Número de documento"
          className="border p-4 rounded-xl w-full mb-4"
          value={paciente.numero_documento}
          onFocus={(e) => (inputActivo.current = e.target)}
          onChange={(e) =>
            handleChange(
              "numero_documento",
              e.target.value.replace(/\D/g, "")
            )
          }
        />

        <div className="flex gap-4">
          <button
            className="bg-green-600 text-white px-8 py-4 rounded-xl w-full"
            onClick={handleGuardar}
          >
            Guardar Registro
          </button>

          <button
            className="bg-red-600 text-white px-8 py-4 rounded-xl w-full"
            onClick={() => navigate(-1)}
          >
            Cancelar
          </button>
        </div>
      </div>
    </div>
  );
}
