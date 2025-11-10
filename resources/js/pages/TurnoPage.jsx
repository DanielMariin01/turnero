import react  from "react";
import { useLocation } from "react-router-dom";

export default function TurnoPage(){


const location = useLocation();
const turno = location.state?.turno


 if (!turno) {
    return <div className="p-10 text-center">No se encontró información del turno.</div>;
  }

  return (
    <div className="min-h-screen flex items-center justify-center p-10">
      <div className="bg-white p-8 rounded-2xl shadow-lg text-center max-w-md w-full">
        <h2 className="text-6xl font-extrabold text-blue-700 mb-4">{turno.numero_turno}</h2>
        <p className="text-xl font-semibold mb-2">{turno.motivo} • {turno.condicion ?? 'Ninguna'}</p>
        <p className="text-lg text-gray-600 mb-6">{turno.fk_paciente.apellido} </p>
        <p className="text-lg">Fecha: {turno.fecha} • Hora: {turno.hora}</p>
        <p className="mt-4 text-green-600 font-semibold">{turno.estado}</p>
      </div>
    </div>
  );



}