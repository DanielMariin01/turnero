import React from 'react';
import ReactDOM from 'react-dom/client';
import '../css/app.css';
import Formulario from "./components/formulario.jsx";
import Tarjeta from "./components/Tarjeta";


function App() {
  const manejarClick = (opcion) => {
    console.log(`Seleccionaste: ${opcion}`);
  };

  return (
<div className="min-h-screen flex flex-col justify-center items-center bg-clinica bg-gradient-to-b from-blue-50 to-white">
      {/* Encabezado de bienvenida */}
    
   

      {/* Contenedor de tarjetas */}
      <div className="flex justify-center items-center bg-gradient-to-b from-blue-50 to-white">
        <Formulario 
        />
 
    </div>

      {/* Pie de página o mensaje de confianza */}
  
    </div>

  );
}

ReactDOM.createRoot(document.getElementById('root')).render(<App />);

