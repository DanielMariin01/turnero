import React from "react";
import ReactDOM from "react-dom/client";
import "../css/app.css";
import AppRouter from "./router";

// Componente principal
function App() {
  return (
    <div className="min-h-screen flex flex-col justify-center items-center bg-clinica from-blue-50 to-white">
      < AppRouter/>
    </div>
  );
}

// Renderiza la aplicación en el div con id="app"
ReactDOM.createRoot(document.getElementById("root")).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>
);

