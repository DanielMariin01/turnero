import React, { use } from "react";
import { useState } from "react";
import { useNavigate } from "react-router-dom";



export default function Formulario() {

const [cargando,setCargando] = useState(false);
 const navigate = useNavigate();

  const [paciente, setPaciente] = useState({
    nombre: "",
    apellido: "",
    tipo_documento: "",
    numero_documento: "",
    condicion_especial: "",

  });

  const [mensaje, setMensaje] = useState("");

  const handleBuscar = async () => {
      setCargando(true);
    try {
     const response = await fetch(`http://127.0.0.1:8000/pacientes/${paciente.numero_documento}`);
      if (!response.ok) throw new Error("paciente no encontrado");

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
        navigate("/bienvenida", { state: { paciente: data } });
    } catch (error) {
      setMensaje("Error al conectar con el servidor ⚠️");
    }
    finally {
    setCargando(false);
  }
  };




    return (
      <div className="max-w-5xl mx-auto bg-white shadow-lg rounded-2xl p-10">
  <h2 className="text-3xl font-bold mb-8 text-center text-blue-700">
    Formulario de ingreso
  </h2>

  {/* Fila: Documento */}
  <div className="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
    <div>
      <label className="block text-2xl font-semibold mb-3 text-gray-800">
        Seleccione su tipo de documento
      </label>
      <select
        value={paciente.tipo_documento}
        onChange={(e) => setPaciente(e.target.value)}
        className="border border-gray-400 rounded-2xl p-5 w-full text-xl"
        placeholder="Seleccione el tipo de documento"
      >
        
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
  onChange={(e) => {
    const valor = e.target.value;
    // Solo permitir dígitos del 0 al 9
    if (/^\d*$/.test(valor)) {
      setPaciente({ ...paciente, numero_documento: valor });
    }
  }}
  placeholder="Ingrese su numero de documento"
/>

     
      </div>
    </div>
  </div>


 

{cargando && (
  <div className="flex flex-col items-center justify-center mt-6">
    <div className="animate-spin rounded-full h-12 w-12 border-t-4 border-blue-500 border-solid"></div>
    <p className="mt-4 text-blue-700 text-lg font-medium">
      Buscando paciente, por favor espere...
    </p>
  </div>
)}

   <button
          type="button"
          onClick={handleBuscar}
          className="bg-green-600 text-white px-10 py-5 rounded-2xl hover:bg-green-700 w-full text-2xl font-bold"
        >
          Ingresar
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