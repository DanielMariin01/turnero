import { use } from "react";
import Tarjeta from "../components/Tarjeta";
import { useLocation } from "react-router-dom";



export default function BienvenidaPage() {

const Location = useLocation();
const paciente = Location.state?.paciente || null;
const formatearNombre = (texto) => {
  if (!texto) return "";
  return texto.charAt(0).toUpperCase() + texto.slice(1).toLowerCase();
};


  return (

<div className="min-h-screen bg-clinica from-blue-50 to-blue-100 flex flex-col items-center justify-center p-10">
      <h1 className="text-4xl font-bold text-blue-800 mb-6">
        Bienvenido {formatearNombre(paciente.nombre)} {formatearNombre(paciente.apellido)}!
      </h1>
      <p className="text-xl text-gray-700 mb-12 text-center">
        Gracias por visitarnos en la Clínica Central del Eje.  
        Por favor, seleccione una de las siguientes opciones:
      </p>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 justify-items-center">
        <Tarjeta
          titulo="Consulta Externa"
          icono="🏥"
          color="blue"
          onClick={() => alert("Ingresando a Admisiones...")}
        />
        <Tarjeta
          titulo="Oncología"
          icono="🧬"
          color="pink"
          onClick={() => alert("Ingresando a Oncología...")}
        />
        <Tarjeta
          titulo="Laboratorios"
          icono="🧪"
          color="green"
          onClick={() => alert("Ingresando a Laboratorios...")}
        />
      </div>
    </div>
  );



}
