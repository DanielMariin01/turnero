import React from "react";
import { motion } from "framer-motion";

export default function Tarjeta({ titulo, icono, color, onClick }) {
  return (
    <motion.button
      onClick={onClick}
      whileHover={{
        scale: 1.05,
        boxShadow: "0px 8px 25px rgba(0, 0, 0, 0.15)",
      }}
      whileTap={{ scale: 0.95 }}
      transition={{ duration: 0.3, ease: "easeInOut" }}
      className={`flex flex-col justify-center items-center 
                  w-80 h-80 bg-white rounded-3xl border-4 border-${color}-300 
                  shadow-md hover:border-${color}-500 
                  focus:outline-none focus:ring-4 focus:ring-${color}-200 
                  p-6 text-center`}
    >
      <span className="text-7xl mb-4">{icono}</span>
      <h2 className="text-3xl font-bold text-blue-800 mb-2">
        {titulo}
      </h2>
      <p className="text-lg text-gray-600 leading-tight max-w-[80%]">
        Toque aquí para generar su turno
      </p>
    </motion.button>
  );
}

