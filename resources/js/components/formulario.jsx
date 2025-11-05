import React, { use } from "react";
import { useState } from "react";



export default function Formulario() {

  const [tipo_documento, setTipo_documento] = useState("");
  const [numero_documento, setNumero_documento] = useState("");
  const [condicion_especial, setCondicion_especial] = useState("");
  const [nombre, setNombre] = useState("");
  const [apellido, setApellido] = useState("");


  const [paciente, setPaciente] = useState({
    nombre: "",
    apellido: "",
    tipp_documento: "",
    numero_documento: "",
    condicion_especial: "",

  });
  const [mensaje, setMensaje] = useState("");

  const handleBuscar = async () => {
    try {
      const response = await fetch(`/api/pacientes/${numero_documento}`);
      if (!response.ok) throw new Error("Error al consultar el paciente");

      const data = await response.json();

      if (data) {
        setPaciente({
          nombre: data.nombre || "",
          apellido: data.apellido || "",
          tipo_documento: data.tipo_documento || "",
          numero_documento: data.numero_documento || "",
          condicion_especial: data.condicion_especial || "",
          
        });
        setMensaje("Paciente encontrado ✅");
      } else {
        setMensaje("No se encontró el paciente ❌");
      }
    } catch (error) {
      setMensaje("Error al conectar con el servidor ⚠️");
    }
  };

  const handleGuardar = async (e) => {
    e.preventDefault();

    try {
      const response = await fetch("/api/pacientes", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ numero_documento, ...paciente }),
      });

      const result = await response.json();
      setMensaje(result.message || "Datos guardados correctamente ✅");
    } catch (error) {
      setMensaje("Error al guardar los datos ❌");
    }
  };


    return (
      <div className="max-w-5xl mx-auto bg-white shadow-lg rounded-2xl p-10">
  <h2 className="text-3xl font-bold mb-8 text-center text-blue-700">
    Ingrese los datos del paciente
  </h2>

  {/* Fila: Documento */}
  <div className="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
    <div>
      <label className="block text-2xl font-semibold mb-3 text-gray-800">
        Tipo de documento
      </label>
      <select
        value={paciente.tipo_documento}
        onChange={(e) => setTipo_documento(e.target.value)}
        className="border border-gray-400 rounded-2xl p-5 w-full text-xl"
      >
        <option value="">-- Seleccione --</option>
        <option value="CC">Cédula de ciudadanía</option>
        <option value="TI">Tarjeta de identidad</option>
        <option value="CE">Cédula de extranjería</option>
        <option value="PA">Pasaporte</option>
      </select>
    </div>

    <div>
      <label className="block text-2xl font-semibold mb-3 text-gray-800">
        Número de documento
      </label>
      <div className="flex gap-4">
        <input
          type="text"
          className="border border-gray-400 rounded-2xl p-5 w-full text-xl"
          value={paciente.numero_documento}
          onChange={(e) => setNumero_documento(e.target.value)}
        />
     
      </div>
    </div>
  </div>


 


   <button
          type="button"
          onClick={handleBuscar}
          className="bg-green-600 text-white px-10 py-5 rounded-2xl hover:bg-green-700 w-full text-2xl font-bold"
        >
          Buscar
        </button>


  {/* Botón Guardar 
  <button
    type="submit"
    onClick={handleGuardar}
    className="bg-green-600 text-white px-10 py-5 rounded-2xl hover:bg-green-700 w-full text-2xl font-bold"
  >
    Guardar
  </button>*/}

  {/* Mensaje */}
  {mensaje && (
    <p className="mt-6 text-center text-xl text-red-600 font-medium">
      {mensaje}
    </p>
  )}
</div>


    );
}