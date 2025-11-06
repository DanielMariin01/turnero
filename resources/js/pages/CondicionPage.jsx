import React from "react";
import { useState } from "react";
import { useLocation , useNavigate } from "react-router-dom";


export default function CondicionPage(){


    const Location = useLocation();
    const navigate = useNavigate();
    const paciente = Location.state?.paciente || null;
    const motivo = Location.state?.motivo || null;

    const [condicion, setCondicion] = useState("ninguna")

   console.log(paciente);
   console

    const confirmarCondicion = () => {

  navigate('/turno', 
    {state :
        {
            paciente,
            motivo,
            condicion

        }
    }
  );
    }

    return(


 <div className="min-h-screen flex flex-col items-center justify-center bg-gradient-to-b from-blue-50 to-white p-8">
      <h1 className="text-4xl font-bold text-blue-700 mb-6 text-center">
        {motivo} - Información adicional
      </h1>
      <p className="text-lg text-gray-700 mb-8 text-center">
        Por favor, indícanos si presentas alguna condición especial 🧑‍🦽
      </p>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
        {[
          { label: "Movilidad reducida", value: "movilidad_reducida" },
          { label: "Embarazada", value: "embarazada" },
          { label: "Adulto mayor", value: "adulto_mayor" },
          { label: "Ninguna", value: "ninguna" },
        ].map((op) => (
          <button
            key={op.value}
            onClick={() => setCondicion(op.value)}
            className={`px-6 py-4 rounded-xl text-xl font-semibold shadow-md transition-all ${
              condicion === op.value
                ? "bg-blue-600 text-white"
                : "bg-white text-blue-700 border border-blue-300"
            }`}
          >
            {op.label}
          </button>
        ))}
      </div>

      <button
        onClick={confirmarCondicion}
        className="px-10 py-4 bg-blue-700 hover:bg-blue-800 text-white text-xl font-bold rounded-2xl shadow-lg transition-all"
      >
        Confirmar ➜
      </button>
    </div>






    );


}