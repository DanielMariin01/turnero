import { BrowserRouter as Router, Routes, Route } from "react-router-dom";
import Formulario from "../components/formulario";
import BienvenidaPage from "../pages/BienvenidaPage";

export default function AppRouter() {
  return (
    <Router>
      <Routes>
        <Route path="/" element={<Formulario />} />
        <Route path="/bienvenida" element={<BienvenidaPage />} />
      </Routes>
    </Router>
  );
}
