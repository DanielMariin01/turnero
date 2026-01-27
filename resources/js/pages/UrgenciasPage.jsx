import React, { useEffect } from "react"; // ← CAMBIO 1
import Tarjeta from "../components/Tarjeta";
import { useNavigate } from "react-router-dom";
import urgencias from "../../imagenes/urgencias.png";
import { connectQZ, isQZConnected } from "../qzConfig"; // ← CAMBIO 2
import Swal from "sweetalert2";

export default function UrgenciasPage() {
    const navigate = useNavigate();

    useEffect(() => {
        connectQZ(); // Ya estaba bien
    }, []);

    const pedirTurno = async () => {
        try {
            const response = await fetch("/api/turno", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    motivo: "urgencias",
                    condicion: null,
                    fk_paciente: null,
                }),
            });
            const data = await response.json();

            // 🔥 IMPRIMIR CON QZ TRAY
            if (data.turno && data.turno.id_turno) {
                const printResponse = await fetch(`/api/turnos/${data.turno.id_turno}/imprimir`);
                const printData = await printResponse.json();

                if (printData.ok && printData.comandos) {
                    // Conectar a QZ Tray e imprimir
                    if (!isQZConnected()) { // ← CAMBIO 3
                        await connectQZ();    // ← CAMBIO 3
                    }

                    const config = window.qz.configs.create("TurneroPrinter");
                    const data_print = [{
                        type: 'raw',
                        format: 'base64',
                        data: printData.comandos
                    }];

                    await window.qz.print(config, data_print);
                    console.log("✅ Ticket impreso con QZ Tray");
                }
            }

            await Swal.fire({
                icon: "success",
                title: "¡Turno creado!",
                text: "Tu turno de urgencias ha sido registrado.",
                confirmButtonColor: "#3085d6",
            });

            setTimeout(() => {
                navigate("/urgencias");
            }, 3000);

        } catch (error) {
            console.error(error);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "No se pudo generar el turno.",
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