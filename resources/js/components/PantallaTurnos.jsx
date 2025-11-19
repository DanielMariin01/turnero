import { useState, useEffect, useRef, use } from "react";

function PantallaTurnos() {
  const [turno, setTurno] = useState(null);
  const [sonidoActivado, setSonidoActivado] = useState(false);
  const prevTurnoRef = useRef(null);
  const audioRef = useRef(null);
  const [turnosLlamados, setTurnosLlamados] = useState([]);

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

//FUNCION TURNOS LLAMADOS
useEffect(() => {

 const fetchTurnosLlamados = async () => {
    try {
        const res = await fetch("http://127.0.0.1:8000/api/turnos-llamados");
        const data = await res.json();
        setTurnosLlamados(data);
      } catch (error) {
        console.error("Error al obtener turnos llamados:", error);
      }
   };

    fetchTurnosLlamados();
    const interval = setInterval(fetchTurnosLlamados, 2000);
   return () => clearInterval(interval);

}, []);

  // Pantalla inicial para activar sonido
  if (!sonidoActivado) {
    return (
      <div className="h-screen flex items-center justify-center bg-color text-white">
        <button
          onClick={activarSonido}
          className="px-8 py-4 text-3xl bg-white rounded-xl shadow-lg hover:bg-green-500/70"
        >
          🔊 Activar sonido
        </button>
      </div>
    );
  }

  if (!turno) return <div>No hay turno llamado</div>;

  return (
<div className="p-10 max-w-7xl mx-auto">

  {/* CONTENEDOR DIVIDIDO EN 2 COLUMNAS */}
  <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">

    {/* --- PANEL PRINCIPAL (antes derecha → AHORA IZQUIERDA) --- */}
    <div className="bg-white shadow-2xl rounded-3xl p-10 text-center border-4">
      <h1 className="text-4xl font-extrabold text-gray-800 mb-6 tracking-wide">
        Turno Actual
      </h1>

      <div className="bg-color-700 text-white rounded-xl py-6 px-4 mb-8 shadow-inner">
        <p className="text-7xl md:text-8xl lg:text-9xl font-black mt-2 leading-none">
          {turno.numero_turno}
        </p>
      </div>

      <div className="text-gray-800">
        <p className="text-3xl font-light mb-2">Paciente:</p>
        <p className="text-4xl md:text-5xl font-bold uppercase tracking-wider">
          {turno.nombre} {turno.apellido}
        </p>
      </div>
    </div>

    {/* --- TABLA DE LLAMADOS (antes izquierda → AHORA DERECHA) --- 
    <div className="bg-white shadow-2xl rounded-3xl p-10 text-center border-4">
    <div className="w-full">
      <h2 className="text-4xl font-extrabold text-bg-white mb-6 tracking-wide">
        Pacientes Llamados
      </h2>

      <table className="w-full table-auto border-collapse shadow-lg bg-white">
        <thead className="bg-sky-500 text-white">
          <tr>
            <th className="p-3">Turno</th>
            <th className="p-3">Paciente</th>
            <th className="p-3">Hora Llamado</th>
          </tr>
        </thead>

        <tbody>
          {turnosLlamados.map((t) => (
            <tr key={t.id_turno} className="border-b text-center">
              <td className="p-3 font-bold text-xl">{t.numero_turno}</td>
              <td className="p-3">
                {t.paciente?.nombre} {t.paciente?.apellido}
              </td>
              <td className="p-3">
                {new Date(t.updated_at).toLocaleTimeString([], {
                  hour: "2-digit",
                  minute: "2-digit",
                })}
              </td>
            </tr>
          ))}
        </tbody>
      </table>

      {turnosLlamados.length === 0 && (
        <p className="text-center text-gray-500 mt-4">
          No hay pacientes llamados.
        </p>
      )}
    </div>
</div>*/}
  </div>
</div>


  );
}

export default PantallaTurnos;
