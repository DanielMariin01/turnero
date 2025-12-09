
import { useState } from "react";
import { useNavigate } from "react-router-dom";
import logo from "../../imagenes/logo.png";



export default function Formulario() {

const [cargando,setCargando] = useState(false);
 const navigate = useNavigate();
 const [mostrarRegistro, setMostrarRegistro] = useState(false);


  const [paciente, setPaciente] = useState({
    nombre: "",
    apellido: "",
    tipo_documento: "",
    numero_documento: "",
    condicion_especial: "",

  });

  const [mensaje, setMensaje] = useState("");

 const handleBuscar = async () => {
  setCargando(true);
  setMensaje("");

  try {
    const response = await fetch(`/api/pacientes/${paciente.numero_documento}`);

    if (response.status === 404) {
      // paciente NO existe → permitir registro
      setMensaje("Paciente no encontrado. Por favor regístrese.");
      setMostrarRegistro(true);
      setCargando(false);
      return;
    }

    if (!response.ok) throw new Error("Error en el servidor");

    const data = await response.json();

    // paciente existe → rellenar datos y navegar
    setPaciente({
      nombre: data.nombre || "",
      apellido: data.apellido || "",
      tipo_documento: data.tipo_documento || "",
      numero_documento: data.numero_documento || "",
      condicion_especial: data.condicion_especial || "",
    });

    navigate("/bienvenida", { state: { paciente: data } });

  } catch (error) {
    setMensaje("Error al conectar con el servidor ⚠️");
  } finally {
    setCargando(false);
  }
};




    return (
  <div className="max-w-6xl mx-auto bg-white shadow-lg rounded-2xl p-10">
    <h2 className="text-3xl font-bold mb-8 text-center text-color-700">
      Formulario de ingreso
    </h2>

    {/* CONTENEDOR PRINCIPAL: FORMULARIO IZQUIERDA / TECLADO DERECHA */}
    <div className="grid grid-cols-1 md:grid-cols-2 gap-10">

      {/* ---------------------- COLUMNA IZQUIERDA: FORMULARIO ---------------------- */}
      <div>
        {/* Tipo de documento */}
        <label className="block text-2xl font-semibold mb-3 text-gray-800">
          Seleccione su tipo de documento
        </label>
        <select
          value={paciente.tipo_documento}
          onChange={(e) =>
            setPaciente({ ...paciente, tipo_documento: e.target.value })
          }
          className="border border-gray-400 rounded-2xl p-5 w-full text-xl mb-8"
        >
          <option value="">Seleccione...</option>
          <option value="CC">Cédula de ciudadanía</option>
          <option value="TI">Tarjeta de identidad</option>
          <option value="CE">Cédula de extranjería</option>
          <option value="PA">Pasaporte</option>
          <option value="RC">Registro Civil</option>
        </select>

        {/* Número documento */}
        <label className="block text-2xl font-semibold mb-3 text-gray-800">
          Número de documento
        </label>

        <input
          type="text"
          className="border border-gray-400 rounded-2xl p-5 w-full text-xl mb-8"
          value={paciente.numero_documento}
          onChange={(e) => {
            const valor = e.target.value;
            if (/^\d*$/.test(valor)) {
              setPaciente({ ...paciente, numero_documento: valor });
            }
          }}
          placeholder="Ingrese su número de documento"
        />

        {/* Botón */}
       <div className="flex gap-4">
  <button
    type="button"
    onClick={handleBuscar}
    className="bg-color-600 text-white px-10 py-5 rounded-2xl hover:bg-color-700 w-full text-2xl font-bold"
  >
    Ingresar
  </button>

  <button
    type="button"
  onClick={() => navigate("/registro")}
    className="bg-sky-500 text-white px-10 py-5 rounded-2xl hover:bg-gray-600 w-full text-2xl font-bold"
  >
    Registrarse
  </button>
</div>


        {/* Mensaje */}
        {mensaje && (
          <p className="mt-6 text-center text-xl text-red-600 font-medium">
            {mensaje}
          </p>
        )}

        {/* Cargando */}
        {cargando && (
          <div className="flex flex-col items-center justify-center mt-6 mb-8">
            <div className="relative">
              <div className="animate-spin rounded-full h-20 w-20 border-t-4 border-b-4 border-green-500"></div>
              <div className="absolute inset-0 flex items-center justify-center">
                <img src={logo} alt="logo" className="w-10 h-10 object-contain" />
              </div>
            </div>
          </div>
        )}
      </div>

      {/* ---------------------- COLUMNA DERECHA: TECLADO NUMÉRICO ---------------------- */}
      <div className="flex justify-center">
        <div className="grid grid-cols-3 gap-4 w-60">

          {["1","2","3","4","5","6","7","8","9","0"].map((num) => (
            <button
              key={num}
              className="bg-gray-200 hover:bg-gray-300 text-3xl font-bold py-6 rounded-xl shadow"
              onClick={() =>
                setPaciente({
                  ...paciente,
                  numero_documento: paciente.numero_documento + num,
                })
              }
            >
              {num}
            </button>
          ))}

          {/* Botón borrar */}
          <button
            className="col-span-3 bg-red-500 hover:bg-red-600 text-white text-2xl font-bold py-4 rounded-xl shadow"
            onClick={() =>
              setPaciente({
                ...paciente,
                numero_documento: paciente.numero_documento.slice(0, -1),
              })
            }
          >
            Borrar
          </button>
        </div>
      </div>

    </div>
  </div>
);

}