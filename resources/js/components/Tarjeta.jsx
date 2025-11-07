import React from "react";
import { motion } from "framer-motion";




export default function Tarjeta({ titulo, color, onClick, imagen, descripcion }) {
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
                  w-80 h-82 bg-white rounded-3xl border-4 border-${color}-300 
                  shadow-md hover:border-${color}-500 
                  focus:outline-none focus:ring-4 focus:ring-${color}-200 
                  p-6 text-center`}
    >
           {imagen && (
        <img
          src={imagen}
          alt={titulo}
          descripcion = {descripcion}
          className="w-30 h-30 object-cover mb-6 rounded-full border-4 border-white shadow-lg"
        />
      )}
 
      <h2 className="text-3xl font-bold text-blue-800 mb-2">
        {titulo}
      </h2>
      <p className="text-lg text-gray-600 leading-tight max-w-[80%]">
       {descripcion}
      </p>
    </motion.button>
  );
}

