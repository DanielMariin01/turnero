import React from "react";
import { motion } from "framer-motion";

export default function Tarjeta({ titulo, color, onClick, imagen, descripcion }) {
  return (
    <div className="relative">
      {/* Anillo animado alrededor de la tarjeta 
      <motion.div
        className="absolute inset-0 rounded-3xl"
        style={{
          padding: "8px",
          background: `linear-gradient(45deg, #00B5B5, #26C2C2, #4DCFCF, #80DDDD, #00B5B5)`,
          backgroundSize: "300% 300%",
        }}
        animate={{
          backgroundPosition: ["0% 50%", "100% 50%", "0% 50%"],
          scale: [1, 1.08, 1],
          rotate: [0, 5, 0, -5, 0],
        }}
        transition={{
          backgroundPosition: {
            duration: 2,
            ease: "linear",
            repeat: Infinity,
          },
          scale: {
            duration: 1.5,
            ease: "easeInOut",
            repeat: Infinity,
          },
          rotate: {
            duration: 2,
            ease: "easeInOut",
            repeat: Infinity,
          },
        }}
      />*/}

      {/* Partículas brillantes alrededor 
      {[...Array(8)].map((_, i) => (
        <motion.div
          key={i}
          className="absolute w-3 h-3 rounded-full"
          style={{
            top: "50%",
            left: "50%",
            backgroundColor: "#B3EAEA",
            boxShadow: "0 0 10px #4DCFCF",
          }}
          animate={{
            x: [
              0,
              Math.cos((i * Math.PI * 2) / 8) * 180,
              0,
            ],
            y: [
              0,
              Math.sin((i * Math.PI * 2) / 8) * 180,
              0,
            ],
            opacity: [0, 1, 0],
            scale: [0, 1.5, 0],
          }}
          transition={{
            duration: 2,
            ease: "easeOut",
            repeat: Infinity,
            delay: i * 0.2,
          }}
        />
      ))}*/}

      <motion.button
        onClick={onClick}
        whileHover={{
          scale: 1.15,
          rotate: [0, -2, 2, -2, 0],
          boxShadow: "0px 15px 40px rgba(0, 181, 181, 0.4)",
        }}
        whileTap={{ scale: 0.9, rotate: 0 }}
        animate={{
          y: [0, -15, 0],
          boxShadow: [
            "0px 8px 20px rgba(0, 181, 181, 0.2)",
            "0px 20px 40px rgba(0, 181, 181, 0.35)",
            "0px 8px 20px rgba(0, 181, 181, 0.2)",
          ],
        }}
        transition={{
          y: {
            duration: 1.2,
            ease: "easeInOut",
            repeat: Infinity,
          },
          boxShadow: {
            duration: 1.2,
            ease: "easeInOut",
            repeat: Infinity,
          },
        }}
        className={`relative flex flex-col justify-center items-center 
                    w-80 h-82 bg-white rounded-3xl border-4 border-white
                    focus:outline-none focus:ring-4 
                    p-6 text-center overflow-hidden z-10`}
        style={{
          focusRing: "4px solid #80DDDD",
        }}
      >
        {/* Destello rotatorio 
        <motion.div
          className="absolute inset-0 opacity-30"
          style={{
            background: `conic-gradient(from 0deg, transparent, rgba(77, 207, 207, 0.8), transparent)`,
          }}
          animate={{
            rotate: 360,
          }}
          transition={{
            duration: 3,
            ease: "linear",
            repeat: Infinity,
          }}
        />*/}

        {/* Onda expansiva 
        <motion.div
          className="absolute inset-0 rounded-3xl border-4"
          style={{
            borderColor: "#26C2C2",
          }}
          animate={{
            scale: [1, 1.3],
            opacity: [0.8, 0],
          }}
          transition={{
            duration: 1.5,
            ease: "easeOut",
            repeat: Infinity,
          }}
        />*/}

        {imagen && (
          <motion.img
            src={imagen}
            alt={titulo}
            className="w-30 h-30 object-cover mb-6 rounded-full border-4 border-white shadow-lg relative z-10"
      
          />
        )}

        <motion.h2
          className="text-3xl font-bold mb-2 relative z-10"
          style={{
            color: "#006F6F",
          }}
          animate={{
            scale: [1, 1.05, 1],
          }}
          transition={{
            duration: 1.2,
            ease: "easeInOut",
            repeat: Infinity,
          }}
        >
          {titulo}
        </motion.h2>

        <p className="text-lg text-gray-600 leading-tight max-w-[80%] relative z-10">
          {descripcion}
        </p>

        {/* Texto pulsante "TOCA AQUÍ" */}
        <motion.div
          className="absolute bottom-6 left-1/2 transform -translate-x-1/2 
                     text-white font-bold px-6 py-2 rounded-full shadow-lg"
          style={{
            background: `linear-gradient(135deg, #00B5B5, #26C2C2)`,
          }}
          animate={{
            scale: [1, 1.2, 1],
            y: [0, -5, 0],
          }}
          transition={{
            duration: 0.8,
            ease: "easeInOut",
            repeat: Infinity,
          }}
        >
          👆 ¡TOCA AQUÍ!
        </motion.div>
      </motion.button>
    </div>
  );
}