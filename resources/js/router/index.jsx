import { BrowserRouter as Router, Routes, Route } from "react-router-dom";
import Formulario from "../components/formulario";
import BienvenidaPage from "../pages/BienvenidaPage";
import CondicionPage from "../pages/CondicionPage";


export default function AppRouter() {
  return (
    <Router>
      <Routes>
        <Route path="/" element={<Formulario />} />
        <Route path="/bienvenida" element={<BienvenidaPage />} />
        <Route path="/condicion" element={<CondicionPage />} />
      </Routes>
    </Router>
  );
}
