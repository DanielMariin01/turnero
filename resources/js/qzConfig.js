// qzConfig.js
// resources/js/qzConfig.js
// Configuración SEGURA de QZ Tray con firma en backend Laravel

let isQZInitialized = false;

/**
 * Obtener el certificado público desde Laravel
 * Este endpoint SÍ es público y seguro
 */
async function getCertificate() {
    try {
        const response = await fetch('/api/qz/certificate', {
            cache: 'no-store',
            headers: { 'Content-Type': 'text/plain' }
        });
        
        if (!response.ok) {
            throw new Error(`Error al obtener certificado: ${response.status}`);
        }
        
        const cert = await response.text();
        console.log('✅ Certificado obtenido del backend');
        return cert;
    } catch (error) {
        console.error('❌ Error obteniendo certificado:', error);
        throw error;
    }
}

/**
 * Firmar mensaje usando el backend (SEGURO)
 * La clave privada NUNCA sale del servidor
 */
async function signMessage(toSign) {
    try {
        const response = await fetch('/api/qz/sign', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/plain'
            },
            body: JSON.stringify({ request: toSign })
        });

        if (!response.ok) {
            throw new Error(`Error al firmar mensaje: ${response.status}`);
        }

        const signature = await response.text();
        console.log('✅ Mensaje firmado por el backend');
        return signature;
    } catch (error) {
        console.error('❌ Error firmando mensaje:', error);
        throw error;
    }
}

/**
 * Configurar la seguridad de QZ Tray
 * Debe llamarse antes de conectar
 */
export function setupQZ() {
    if (!window.qz) {
        console.error('❌ QZ Tray no está disponible en window.qz');
        throw new Error('QZ Tray no está cargado');
    }

    if (isQZInitialized) {
        console.log('ℹ️ QZ Tray ya está configurado');
        return;
    }

    try {
        // Configurar el certificado público (se obtiene del backend)
        window.qz.security.setCertificatePromise(async () => {
            return await getCertificate();
        });

        // Configurar la firma (se hace en el backend)
        window.qz.security.setSignaturePromise(async (toSign) => {
            return await signMessage(toSign);
        });

        // Establecer el algoritmo de firma
        window.qz.security.setSignatureAlgorithm('SHA512');

        isQZInitialized = true;
        console.log('✅ QZ Tray configurado con firma segura en backend');
    } catch (error) {
        console.error('❌ Error configurando QZ:', error);
        throw error;
    }
}

/**
 * Conectar a QZ Tray de forma segura
 * @returns {Promise<boolean>} true si la conexión fue exitosa
 */
export async function connectQZ() {
    try {
        if (!window.qz) {
            throw new Error('QZ Tray no está cargado. Asegúrate de tener QZ Tray corriendo.');
        }

        // Configurar seguridad primero
        setupQZ();

        // Verificar si ya está conectado
        if (window.qz.websocket.isActive()) {
            console.log('ℹ️ QZ Tray ya está conectado');
            return true;
        }

        // Conectar
        console.log('🔄 Conectando a QZ Tray...');
        await window.qz.websocket.connect();
        console.log('✅ Conectado a QZ Tray exitosamente');
        
        return true;
    } catch (error) {
        console.error('❌ Error al conectar a QZ Tray:', error);
        console.error('Detalles:', error.message);
        return false;
    }
}

/**
 * Desconectar de QZ Tray
 */
export async function disconnectQZ() {
    try {
        if (window.qz && window.qz.websocket.isActive()) {
            await window.qz.websocket.disconnect();
            console.log('✅ Desconectado de QZ Tray');
        }
    } catch (error) {
        console.error('❌ Error al desconectar:', error);
    }
}

/**
 * Verificar si QZ Tray está conectado
 * @returns {boolean}
 */
export function isQZConnected() {
    return window.qz && window.qz.websocket.isActive();
}

/**
 * Obtener lista de impresoras disponibles
 * @returns {Promise<Array<string>>}
 */
export async function getPrinters() {
    try {
        if (!isQZConnected()) {
            await connectQZ();
        }
        
        const printers = await window.qz.printers.find();
        console.log('🖨️ Impresoras encontradas:', printers);
        return printers;
    } catch (error) {
        console.error('❌ Error obteniendo impresoras:', error);
        return [];
    }
}