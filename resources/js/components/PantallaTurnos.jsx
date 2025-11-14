import react, { useState, useEffect } from 'react';

function PantallaTurnos() {
  const [turno, setTurno] = useState(null);

  useEffect(() => {
    const fetchTurno = async () => {

   try {
    const res = await fetch('http://127.0.0.1:8000/api/turno-ultimo');
    if (!res.ok) throw new Error('Error en la API');
    const data = await res.json();
    setTurno(data);
  } catch (error) {
    console.error('Error al obtener el turno:', error);
    setTurno(null);
  }

    };

    // Llamada inicial
    fetchTurno();

    // Polling cada 2 segundos
    const interval = setInterval(fetchTurno, 2000);

    return () => clearInterval(interval);
  }, []);

  if (!turno) return <div>No hay turno llamado</div>;

  return (

      
  <div className="bg-white shadow-2xl rounded-3xl p-10 max-w-2xl w-full text-center mx-auto border-4 border-color-700/50 transform hover:scale-[1.01] transition duration-300">
    
    {/* Título Principal */}
    <h1 className="text-4xl font-extrabold text-gray-800 mb-6 tracking-wide">
        Turno Actual
    </h1>

    {/* Bloque del Número de Turno - Destacado y Alto Contraste */}
    <div className="bg-color-700 text-white rounded-xl py-6 px-4 mb-8 shadow-inner">
    
        <p className="text-7xl md:text-8xl lg:text-9xl font-black mt-2 leading-none">
            {turno.numero_turno}
        </p>
    </div>

    {/* Separador Visual (Estética y jerarquía) */}
    <div className="border-t border-gray-300 w-1/3 mx-auto my-6"></div>

    {/* Bloque del Paciente - Claro y Legible */}
    <div className="text-gray-800">
        <p className="text-3xl font-light mb-2">
            Paciente:
        </p>
        <p className="text-4xl md:text-5xl font-bold uppercase tracking-wider">
            {turno.nombre} {turno.apellido}
        </p>
    </div>

    {/* Mensaje de Espera - Accesible y Amigable */}
    <p className="text-xl text-gray-600 mt-10">
        Por favor, espere su turno. <span role="img" aria-label="Mano saludando">👋</span>
    </p>

</div>
  );
}

export default PantallaTurnos;
