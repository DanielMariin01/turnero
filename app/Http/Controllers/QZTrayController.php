<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QZTrayController extends Controller
{
    /**
     * Retorna el certificado público (digital-certificate.txt)
     * Este SÍ puede ser público y seguro
     */
    public function getCertificate()
    {
        try {
            $certificatePath = storage_path('app/qz-tray/digital-certificate.txt');
            
            if (!file_exists($certificatePath)) {
                Log::error('Certificado no encontrado en: ' . $certificatePath);
                return response()->json([
                    'error' => 'Certificado no encontrado'
                ], 404);
            }

            $certificate = file_get_contents($certificatePath);
            
            if (empty($certificate)) {
                Log::error('Certificado vacío');
                return response()->json([
                    'error' => 'Certificado vacío'
                ], 500);
            }

            Log::info('✅ Certificado enviado correctamente');
            
            return response($certificate)
                ->header('Content-Type', 'text/plain')
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate');

        } catch (\Exception $e) {
            Log::error('Error al obtener certificado: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al obtener certificado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Firma el mensaje usando la clave privada
     * Esta es la función CRÍTICA que protege tu private key
     */
    public function signMessage(Request $request)
    {
        try {
            // Obtener el mensaje a firmar
            $toSign = $request->input('request');
            
            if (!$toSign) {
                Log::error('No se proporcionó mensaje para firmar');
                return response()->json([
                    'error' => 'No se proporcionó mensaje para firmar'
                ], 400);
            }

            // Ruta de la clave privada (NUNCA expuesta al frontend)
            $privateKeyPath = storage_path('app/qz-tray/private-key.pem');
            
            if (!file_exists($privateKeyPath)) {
                Log::error('Clave privada no encontrada en: ' . $privateKeyPath);
                return response()->json([
                    'error' => 'Clave privada no encontrada'
                ], 404);
            }

            // Leer la clave privada
            $privateKeyContent = file_get_contents($privateKeyPath);
            
            if (empty($privateKeyContent)) {
                Log::error('Clave privada vacía');
                return response()->json([
                    'error' => 'Clave privada vacía'
                ], 500);
            }

            // Cargar la clave privada
            $privateKey = openssl_get_privatekey($privateKeyContent);
            
            if (!$privateKey) {
                Log::error('No se pudo cargar la clave privada');
                return response()->json([
                    'error' => 'No se pudo cargar la clave privada'
                ], 500);
            }

            // Firmar el mensaje con SHA512
            $signature = null;
            $success = openssl_sign(
                $toSign, 
                $signature, 
                $privateKey, 
                OPENSSL_ALGO_SHA512
            );

            // Liberar la clave privada de memoria
            openssl_free_key($privateKey);

            if (!$success) {
                Log::error('Error al firmar el mensaje');
                return response()->json([
                    'error' => 'Error al firmar el mensaje'
                ], 500);
            }

            Log::info('✅ Mensaje firmado correctamente');

            // Retornar la firma en base64
            return response(base64_encode($signature))
                ->header('Content-Type', 'text/plain')
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate');

        } catch (\Exception $e) {
            Log::error('Error al firmar mensaje: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al firmar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Método de prueba para verificar que todo funciona
     * Puedes acceder a: /api/qz/test
     */
    public function test()
    {
        $certificatePath = storage_path('app/qz-tray/digital-certificate.txt');
        $privateKeyPath = storage_path('app/qz-tray/private-key.pem');

        $status = [
            'certificate_exists' => file_exists($certificatePath),
            'certificate_readable' => is_readable($certificatePath),
            'certificate_path' => $certificatePath,
            'private_key_exists' => file_exists($privateKeyPath),
            'private_key_readable' => is_readable($privateKeyPath),
            'private_key_path' => $privateKeyPath,
            'openssl_available' => function_exists('openssl_sign'),
        ];

        if ($status['certificate_exists']) {
            $certContent = file_get_contents($certificatePath);
            $status['certificate_size'] = strlen($certContent);
            $status['certificate_preview'] = substr($certContent, 0, 50) . '...';
        }

        if ($status['private_key_exists']) {
            $keyContent = file_get_contents($privateKeyPath);
            $status['private_key_size'] = strlen($keyContent);
            $status['private_key_valid'] = openssl_get_privatekey($keyContent) !== false;
        }

        return response()->json($status, 200);
    }
}