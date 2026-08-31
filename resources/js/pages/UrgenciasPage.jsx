import React, { useEffect, useRef, useState } from "react";
import Tarjeta from "../components/Tarjeta";
import { useNavigate } from "react-router-dom";
import urgencias from "../../imagenes/urgencias.png";
import { connectQZ, isQZConnected } from "../qzConfig";
import Swal from "sweetalert2";

export default function UrgenciasPage() {
    const navigate = useNavigate();
    const [generando, setGenerando] = useState(false);
    const procesandoRef = useRef(false); // bloqueo inmediato, no depende del re-render

    useEffect(() => {
        connectQZ().catch(err => console.error("QZ no conectó al iniciar:", err));
    }, []);

    const pedirTurno = async () => {
        // 🔒 Bloqueo inmediato contra doble clic/doble tap
        if (procesandoRef.current) return;
        procesandoRef.current = true;
        setGenerando(true);

        // Alerta de "generando turno"
        Swal.fire({
            title: "Generando turno...",
            text: "Por favor espera",
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        let turnoCreado = null;

        // PASO 1: crear el turno
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

            if (!response.ok) {
                throw new Error(`Error del servidor: ${response.status}`);
            }

            const data = await response.json();
            turnoCreado = data.turno;
        } catch (error) {
            console.error("Error creando turno:", error);
            await Swal.fire({
                icon: "error",
                title: "Error",
                text: "No se pudo generar el turno.",
                confirmButtonColor: "#d33",
            });
            procesandoRef.current = false;
            setGenerando(false);
            return;
        }

        // PASO 2: imprimir (independiente, no debe bloquear el flujo si falla)
        if (turnoCreado?.id_turno) {
            try {
                const printResponse = await fetch(`/api/turnos/${turnoCreado.id_turno}/imprimir`);

                if (!printResponse.ok) {
                    throw new Error(`Error del servidor: ${printResponse.status}`);
                }

                const printData = await printResponse.json();

                if (printData.ok && printData.comandos) {
                    if (!isQZConnected()) {
                        await connectQZ();
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
            } catch (printError) {
                console.error("Error imprimiendo:", printError);
                await Swal.fire({
                    icon: "warning",
                    title: "Turno creado, pero no se pudo imprimir",
                    text: "Verifica la impresora",
                    confirmButtonColor: "#f0ad4e",
                });
                procesandoRef.current = false;
                setGenerando(false);
                setTimeout(() => navigate("/urgencias"), 3000);
                return;
            }
        }

        // PASO 3: éxito
        await Swal.fire({
            icon: "success",
            title: "¡Turno creado!",
            text: "Tu turno de urgencias ha sido registrado.",
            confirmButtonColor: "#3085d6",
        });

        procesandoRef.current = false;
        setGenerando(false);

        setTimeout(() => {
            navigate("/urgencias");
        }, 3000);
    };

    return (
        <div>
            <Tarjeta
                titulo="Pedir turno"
                color="blue"
                imagen={urgencias}
                onClick={pedirTurno}
                disabled={generando}
            />
        </div>
    );
}