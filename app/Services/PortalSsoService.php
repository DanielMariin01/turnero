<?php

namespace App\Services;

use RuntimeException;

/**
 * Valida el token de ingreso único que emite el Portal Corporativo (JWT firmado con RS256).
 * Solo usa OpenSSL de PHP: no requiere paquetes adicionales en el servidor.
 */
class PortalSsoService
{
    /** Tolerancia por diferencia de reloj entre el portal y este servidor. */
    private const MARGEN_RELOJ_SEGUNDOS = 30;

    public function __construct(
        private readonly string $clavePublica,
        private readonly string $emisor,
        private readonly string $audiencia,
    ) {
    }

    /**
     * @return array<string, mixed> Los datos del token (documento, email, name, jti…).
     *
     * @throws RuntimeException si el token no es válido, está vencido o no es para este aplicativo.
     */
    public function validar(string $token): array
    {
        $partes = explode('.', $token);
        if (count($partes) !== 3) {
            throw new RuntimeException('Token con formato inválido.');
        }

        [$cabecera, $contenido, $firma] = $partes;

        $datosCabecera = $this->decodificarJson($cabecera);
        if (($datosCabecera['alg'] ?? null) !== 'RS256') { // nunca aceptar otro algoritmo (ni "none")
            throw new RuntimeException('Algoritmo no permitido.');
        }

        $firmaValida = openssl_verify(
            "{$cabecera}.{$contenido}",
            $this->base64UrlDecodificar($firma),
            $this->clavePublica,
            OPENSSL_ALGO_SHA256
        );
        if ($firmaValida !== 1) {
            throw new RuntimeException('Firma inválida.');
        }

        $datos = $this->decodificarJson($contenido);
        $this->validarVigenciaYDestino($datos);

        return $datos;
    }

    private function validarVigenciaYDestino(array $datos): void
    {
        $ahora = time();

        if (($datos['exp'] ?? 0) + self::MARGEN_RELOJ_SEGUNDOS < $ahora) {
            throw new RuntimeException('El token venció.');
        }
        if (($datos['iat'] ?? $ahora) - self::MARGEN_RELOJ_SEGUNDOS > $ahora) {
            throw new RuntimeException('El token aún no es válido.');
        }
        if (($datos['iss'] ?? null) !== $this->emisor) {
            throw new RuntimeException('Emisor no reconocido.');
        }
        if (($datos['aud'] ?? null) !== $this->audiencia) {
            throw new RuntimeException('El token no es para este aplicativo.');
        }
        if (empty($datos['jti']) || empty($datos['documento'])) {
            throw new RuntimeException('El token no trae los datos necesarios.');
        }
    }

    private function decodificarJson(string $textoBase64): array
    {
        $datos = json_decode($this->base64UrlDecodificar($textoBase64), true);

        if (! is_array($datos)) {
            throw new RuntimeException('Token con contenido inválido.');
        }

        return $datos;
    }

    private function base64UrlDecodificar(string $texto): string
    {
        $relleno = (4 - strlen($texto) % 4) % 4;

        return (string) base64_decode(strtr($texto, '-_', '+/') . str_repeat('=', $relleno), true);
    }
}
