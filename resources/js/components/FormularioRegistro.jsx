import React, { useState } from "react";
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
    }).then(() => {
      navigate("/"); // volver al formulario principal
    });

    } catch (error) {
      Swal.fire({
      title: "Error",
      text: "Hubo un problema al registrar el paciente.",
      icon: "error",
      confirmButtonText: "Cerrar",
    });
    }
  };

  return (
    <div className="max-w-3xl mx-auto bg-white shadow-lg rounded-2xl p-10 mt-10">
      <h2 className="text-3xl font-bold mb-8 text-center">
        Registro de Paciente
      </h2>

      <input
        type="text"
        placeholder="Nombre"
        className="border p-4 rounded-xl w-full mb-4"
        value={paciente.nombre}
        onChange={(e) => setPaciente({ ...paciente, nombre: e.target.value })}
      />

      <input
        type="text"
        placeholder="Apellido"
        className="border p-4 rounded-xl w-full mb-4"
        value={paciente.apellido}
        onChange={(e) =>
          setPaciente({ ...paciente, apellido: e.target.value })
        }
      />

      <select
        className="border p-4 rounded-xl w-full mb-4"
        value={paciente.tipo_documento}
        onChange={(e) =>
          setPaciente({ ...paciente, tipo_documento: e.target.value })
        }
      >
        <option value="">Tipo de documento</option>
        <option value="CC">Cédula de ciudadanía</option>
        <option value="TI">Tarjeta de identidad</option>
        <option value="CE">Cédula de extranjería</option>
        <option value="PA">Pasaporte</option>
      </select>

      <input
        type="text"
        placeholder="Número de documento"
        className="border p-4 rounded-xl w-full mb-4"
        value={paciente.numero_documento}
        onChange={(e) =>
          setPaciente({
            ...paciente,
            numero_documento: e.target.value.replace(/\D/g, ""),
          })
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
  );
}
