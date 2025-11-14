import { useState, useEffect, useRef } from "react";

function PantallaTurnos() {
  const [turno, setTurno] = useState(null);
  const [sonidoActivado, setSonidoActivado] = useState(false);
  const prevTurnoRef = useRef(null);
  const audioRef = useRef(null);

  // 👉 Función que faltaba
  const activarSonido = () => {
    console.log("🔊 Sonido activado manualmente");
    setSonidoActivado(true);

    // Reproducción inicial obligatoria (Chrome)
    if (audioRef.current) {
      audioRef.current.currentTime = 0;
      audioRef.current.play().catch((err) => console.warn("No se pudo reproducir:", err));
    }
  };

  // Cargar audio una sola vez
  useEffect(() => {
    audioRef.current = new Audio("/audio/audio1.mp3");
  }, []);

  useEffect(() => {
    const fetchTurno = async () => {
      try {
        const res = await fetch("http://127.0.0.1:8000/api/turno-ultimo");
        if (!res.ok) throw new Error("Error en la API");
        const data = await res.json();

        // ▶️ Sonar solo si el turno cambió
        if (
          sonidoActivado &&
          prevTurnoRef.current &&
          prevTurnoRef.current.numero_turno !== data.numero_turno
        ) {
          console.log("🔔 Nuevo turno detectado → SONIDO");
          audioRef.current.currentTime = 0;
          audioRef.current.play().catch((err) => console.error(err));
        }

        prevTurnoRef.current = data;
        setTurno(data);

      } catch (error) {
        console.error("Error al obtener el turno:", error);
      }
    };

    fetchTurno();
    const interval = setInterval(fetchTurno, 2000);
    return () => clearInterval(interval);
  }, [sonidoActivado]);  // ← importante

  // Pantalla inicial para activar sonido
  if (!sonidoActivado) {
    return (
      <div className="h-screen flex items-center justify-center bg-gray-900 text-white">
        <button
          onClick={activarSonido}
          className="px-8 py-4 text-3xl bg-green-500 rounded-xl shadow-lg hover:bg-green-600"
        >
          🔊 Activar sonido
        </button>
      </div>
    );
  }

  if (!turno) return <div>No hay turno llamado</div>;

  return (
    <div className="bg-white shadow-2xl rounded-3xl p-10 max-w-2xl w-full text-center mx-auto border-4 border-color-700/50">
      <h1 className="text-4xl font-extrabold text-gray-800 mb-6 tracking-wide">
        Turno Actual
      </h1>

      <div className="bg-color-700 text-white rounded-xl py-6 px-4 mb-8 shadow-inner">
        <p className="text-7xl md:text-8xl lg:text-9xl font-black mt-2 leading-none">
          {turno.numero_turno}
        </p>
      </div>

      <div className="border-t border-gray-300 w-1/3 mx-auto my-6"></div>

      <div className="text-gray-800">
        <p className="text-3xl font-light mb-2">Paciente:</p>
        <p className="text-4xl md:text-5xl font-bold uppercase tracking-wider">
          {turno.nombre} {turno.apellido}
        </p>
      </div>

      <p className="text-xl text-gray-600 mt-10">
        Por favor, espere su turno 👋
      </p>
    </div>
  );
}

export default PantallaTurnos;
