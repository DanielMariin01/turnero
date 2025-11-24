import React, { useState, useEffect } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import Tarjeta from "../components/Tarjeta";
import Swal from "sweetalert2";


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
  const [cargando , setCargando] = useState(false);


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




  const generarYGuardarTurno = async (condicion) => {
 
    if(!paciente){
      return  alert("no se encontraron datos del paciente")
    }
Swal.fire({
  html: `
    <div style="display: flex; flex-direction: column; align-items: center;">
      
      <div class="loader-hospital"></div>

      <h2 style="font-size: 55px; font-weight: bold; color: #00B5B5; margin-top: 20px;">
        Generando tu turno
      </h2>

      <p style="font-size: 30px; color: #444; margin-top: 10px;">
        Por favor espera un momento
      </p>
    </div>
  `,
  width: "40rem",
  background: "#ffffff",
  showConfirmButton: false,
  allowOutsideClick: false,
  allowEscapeKey: false,
   didOpen: () => {
    Swal.showLoading();
  }
});



   try{

      const payload = {
        fk_paciente: paciente.id_paciente ?? null,           // si usas id en BD local
        motivo: motivo,
        condicion: condicion,

      };

      const res = await fetch("/api/turno",{


  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify(payload),


      });
      const data = await res.json();

      if(!res.ok){
          Swal.close();
      Swal.fire({
        icon: "error",
        title: "Error",
        text: data.message || "Error al generar el turno",
      });
      return;

      }

  Swal.close();

     navigate("/turno", { state: { turno: data.turno } });

   }catch (error) {
      console.error(error);
      alert("Error de conexión con el servidor");
      setCargando(false);
    }




  }



  return (
    <div className="min-h-screen bg-white from-blue-50 to-blue-100 flex flex-col items-center justify-center p-5">

      <div className="bg-color-50 p-6 rounded-2xl shadow-lg text-center mt-6 mb-6">
        <h2 className="text-4xl font-extrabold text-color-900 mb-4">
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
          onClick={() => generarYGuardarTurno("gestante")}
        />

        <Tarjeta
          titulo="Movilidad Reducida"
          color="green"
          imagen={movilidad_reducida1}
          descripcion="Toque aquí para generar su turno"
          onClick={() => generarYGuardarTurno("movilidad_reducida")}
        />

        <Tarjeta
          titulo="Adulto mayor"
          color="green"
          imagen={adulto_mayor}
          descripcion="Toque aquí para generar su turno"
       onClick={() => generarYGuardarTurno("adulto_mayor")}
        />

        <Tarjeta
          titulo="Acompañado por un menor"
          color="green"
          imagen={adulto_niño}
          descripcion="Toque aquí para generar su turno"
     onClick={() => generarYGuardarTurno("acompañado_con_un_menor")}
        />

           <Tarjeta
          titulo="No tengo"
          color="green"
          descripcion="Toque aquí para generar su turno"
            onClick={() => generarYGuardarTurno("ninguna")}
        />


      </div>
    </div>
  );
}
