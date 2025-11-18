import React, { useState, useEffect } from "react";
import { useLocation, useNavigate } from "react-router-dom";




export default function TurnoPage(){


const location = useLocation();
const turno = location.state?.turno
const motivo = location.state?.motivo || null;
const navigate = useNavigate();


 useEffect(() => {
  let timer = setTimeout(() => {
    navigate("/"); // ⬅ Ajusta la ruta si tu menú principal es diferente
  }, 20000); // 20 segundos

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

if (!turno) {
  return (
    <div className="p-10 text-center text-3xl text-gray-700 font-semibold">
      😔 No encontramos la información de tu turno.<br />
      Por favor, intenta nuevamente o acércate a recepción para recibir ayuda.
    </div>
  );
}

return (
 
    <div className="bg-white p-10 rounded-3xl shadow-2xl flex flex-col lg:flex-row items-center justify-between gap-10 border-4 border-color-200 w-full max-w-6xl">

      {/* 🟦 Columna izquierda - Número de turno */}
      <div className="flex-1 text-center lg:text-left">
        <h1 className="text-5xl font-extrabold text-color-800 mb-6">
          ¡Tu turno es!
        </h1>
        <h2 className="text-9xl font-extrabold text-color-700 mb-6  drop-shadow-lg">
          {turno.numero_turno}
        </h2>
       
      </div>

      {/* 🟨 Línea divisoria */}
      <div className="hidden lg:block w-px bg-blue-200 h-96"></div>

      {/* 🟩 Columna derecha - Detalles */}
      <div className="flex-1 text-center lg:text-left">
        <p className="text-3xl text-gray-800 font-semibold mb-4">
          Motivo: <span className="text-color-800 font-bold">{turno.motivo}</span>
        </p>
        <p className="text-3xl text-gray-800 font-semibold mb-4">
          Condición:{" "}
          <span className="text-color-800 font-bold">
            {turno.condicion ?? "Ninguna"}
          </span>
        </p>

        <p className="text-2xl text-gray-700 mb-4">
          Paciente:{" "}
          <span className="font-semibold text-gray-900">
{turno.paciente
  ? `${turno.paciente.nombre} ${turno.paciente.apellido}`
  : "Sin nombre registrado"}
  
          </span>
        </p>

        {/*<div className="bg-blue-50 p-6 rounded-2xl shadow-inner mb-6 w-full">
          <p className="text-2xl text-gray-800">
            📅 <strong>Fecha:</strong> {turno.fecha}
          </p>
          <p className="text-2xl text-gray-800">
            ⏰ <strong>Hora:</strong> {turno.hora}
          </p>
        </div> */}

        {/* 🟣 Mensaje dinámico según el motivo */}
        <p className="text-3xl text-color-800 font-bold mb-6">
          {turno.motivo === "Consulta Externa"
            ? "Por favor, dirígete a Consulta Externa"
            : turno.motivo === "Oncología"
            ? "Por favor, dirígete al área de Oncología "
            : "Por favor, dirígete al área correspondiente"}
        </p>

        {/* 🟢 Botón de ayuda */}
        <div className="text-center lg:text-left">
          <p className="text-2xl text-gray-800 mb-4">
            ¿No sabes cómo llegar? 💭
          </p>
          <button
            onClick={() =>
              window.open("https://www.youtube.com/watch?v=bur6-jFXpmY")
            }
            className="px-8 py-4 bg-color-700 text-white text-2xl font-bold rounded-2xl shadow-lg hover:bg-cyan-800 transition-all"
          >
            Ver video de ayuda ▶️
          </button>
        </div>

     
      </div>
    </div>

);


}