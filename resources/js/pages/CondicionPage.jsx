import React, { useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import Tarjeta from "../components/Tarjeta";

// Imágenes
import gestante from "../../imagenes/gestante.png";
import movilidad_reducida1 from "../../imagenes/movilidad_reducida1.png";
import adulto_mayor from "../../imagenes/adulto_mayor.png";
import adulto_niño from "../../imagenes/adulto_niño.png";

export default function CondicionPage() {
  const location = useLocation();
  const navigate = useNavigate();

  const paciente = location.state?.paciente || null;
  const motivo = location.state?.motivo || null;

  const [condicion, setCondicion] = useState("ninguna");

  console.log(paciente);

  const confirmarCondicion = () => {
    navigate("/turno", {
      state: {
        paciente,
        motivo,
        condicion,
      },
    });
  };

  return (
    <div className="min-h-screen bg-white from-blue-50 to-blue-100 flex flex-col items-center justify-center p-5">
      

      <div className="bg-blue-50 p-6 rounded-2xl shadow-lg text-center mt-6 mb-6">
        <h2 className="text-4xl font-extrabold text-blue-900 mb-4">
          Para atenderte de la mejor manera, ¿podrías indicarnos si presentas
          alguna condición especial?
        </h2>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-8 justify-items-center">
        <Tarjeta
          titulo="Gestante"
          color="green"
          imagen={gestante}
          descripcion="Toque aquí para generar su turno"
          onClick={() => setCondicion("gestante")}
        />

        <Tarjeta
          titulo="Movilidad Reducida"
          color="green"
          imagen={movilidad_reducida1}
          descripcion="Toque aquí para generar su turno"
          onClick={() => setCondicion("movilidad_reducida")}
        />

        <Tarjeta
          titulo="Adulto mayor"
          color="green"
          imagen={adulto_mayor}
          descripcion="Toque aquí para generar su turno"
          onClick={() => setCondicion("adulto_mayor")}
        />

        <Tarjeta
          titulo="Acompañado por un menor"
          color="green"
          imagen={adulto_niño}
          descripcion="Toque aquí para generar su turno"
          onClick={() => setCondicion("adulto_niño")}
        />

           <Tarjeta
          titulo="No tengo"
          color="green"
          descripcion="Toque aquí para generar su turno"
          onClick={() => setCondicion("ninguna")}
        />


      </div>
    </div>
  );
}
