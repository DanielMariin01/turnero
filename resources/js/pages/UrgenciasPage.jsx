import React from "react";
import Tarjeta from "../components/Tarjeta";
import { useNavigate } from "react-router-dom";
import { div } from "framer-motion/client";
import urgencias from "../../imagenes/urgencias.png";
import Swal from "sweetalert2";


export default function UrgenciasPage() {

    const navigate = useNavigate();


    const pedirTurno = async () => {
        try {
            const response = await fetch("/api/turno", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    motivo: "Urgencias",
                    condicion: null,
                    fk_paciente: null,
                }),
            });

            //se convierte la respuesta Json y se le asigna a la variable data
            const data = await response.json();
            // Imprimir automáticamente el turno
            if (data.turno && data.turno.id_turno) {
                // Abrir PDF en nueva ventana
                const printWindow = window.open(
                    `/api/turnos/${data.turno.id_turno}/imprimir`,
                    "_blank"
                );

                // Esperar a que cargue y ejecutar impresión
                if (printWindow) {
                    printWindow.onload = function () {
                        printWindow.print();
                    };
                }
            }

            await Swal.fire({
                icon: "success",
                title: "¡Turno creado!",
                text: `Tu turno de urgencias ha sido registrado exitosamente.`,
                confirmButtonText: "Aceptar",
                confirmButtonColor: "#3085d6",
            });
            // Abrir PDF para imprimir
            //window.open(`/api/turnos/${turno.id}/imprimir`, "_blank");

            // Opcional: volver a inicio luego de imprimir
            setTimeout(() => {
                navigate("/urgencias");
            }, 3000);

        } catch (error) {
            console.error("Error al generar turno", error);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "Hubo un problema al crear el turno. Intenta nuevamente.",
                confirmButtonText: "Aceptar",
                confirmButtonColor: "#d33",
            });
        }
    };



    return (
        <div>
            <Tarjeta
                titulo="Pedir turno"
                color="blue"
                imagen={urgencias}
                onClick={pedirTurno}
            />
        </div>
    );
}