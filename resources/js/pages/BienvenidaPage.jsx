import { use } from "react";
import Tarjeta from "../components/Tarjeta";
import { useLocation , useNavigate} from "react-router-dom";

import oncologia from "../../imagenes/oncologia.png";
import consulta_externa from "../../imagenes/consulta_externa.png";



export default function BienvenidaPage() {

const Location = useLocation();
const paciente = Location.state?.paciente || null;
 const navigate = useNavigate();

const formatearNombre = (texto) => {
  if (!texto) return "";
  return texto.charAt(0).toUpperCase() + texto.slice(1).toLowerCase();
};

const manejarClick = (motivo) =>{

const datosPaciente = { ...paciente, motivo };
  navigate('/condicion', {state : {paciente : datosPaciente}});

}
  return (

<div className="min-h-screen bg-white from-blue-50 to-blue-100 flex flex-col items-center justify-center p-10">
<h1 className="text-5xl font-extrabold text-blue-700 mb-4 text-center animate-fade-in">
  👋 Bienvenido{" "}
  <span className="text-blue-900">
    {formatearNombre(paciente.nombre)} {formatearNombre(paciente.apellido)}!
  </span>
</h1>

<div className="bg-blue-50 p-6 rounded-2xl shadow-lg text-center mt-6 mb-6">
  <h2 className="text-4xl font-extrabold text-blue-900 mb-4">
    ¿Podrías indicar qué necesitas hoy?
  </h2>
  <p className="text-lg text-gray-700">
    Solo elige una de las opciones que ves a continuación 💙
  </p>
</div>
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-8 justify-items-center">
        <Tarjeta
          titulo="Consulta Externa"
          icono="🏥"
          color="blue"
          imagen={consulta_externa}
          onClick={() => manejarClick("Consulta Externa")}
        />
        <Tarjeta
          titulo="Oncología"
          icono="🧬"
          color="pink"
          imagen={oncologia}
          onClick={() => manejarClick("Oncología")}
        />
       
      </div>
    </div>
  );



}
