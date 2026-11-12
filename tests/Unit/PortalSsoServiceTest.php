<?php

namespace Tests\Unit;

use App\Services\PortalSsoService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/** Prueba pura del validador de tokens del portal: no usa la base de datos ni arranca Laravel. */
class PortalSsoServiceTest extends TestCase
{
    private const EMISOR = 'https://portal.test';
    private const AUDIENCIA = 'Turnero';

    private static string $privada;
    private static string $publica;

    public static function setUpBeforeClass(): void
    {
        [self::$privada, self::$publica] = self::generarClaves();
    }

    /** @return array{0: string, 1: string} [privada, pública] */
    private static function generarClaves(): array
    {
        $cnf = self::rutaOpensslCnf();
        $opciones = array_filter(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA, 'config' => $cnf]);

        $llave = openssl_pkey_new($opciones);
        openssl_pkey_export($llave, $privada, null, array_filter(['config' => $cnf]));

        return [$privada, openssl_pkey_get_details($llave)['key']];
    }

    private static function rutaOpensslCnf(): ?string
    {
        foreach ([getenv('OPENSSL_CONF'), dirname(PHP_BINARY) . '/extras/ssl/openssl.cnf'] as $ruta) {
            if ($ruta && is_file($ruta)) {
                return $ruta;
            }
        }

        return null;
    }

    private function servicio(int $margenReloj = PortalSsoService::MARGEN_RELOJ_POR_DEFECTO): PortalSsoService
    {
        return new PortalSsoService(self::$publica, self::EMISOR, self::AUDIENCIA, $margenReloj);
    }

    private function base64Url(string $texto): string
    {
        return rtrim(strtr(base64_encode($texto), '+/', '-_'), '=');
    }

    private function token(array $cambios = [], string $alg = 'RS256', ?string $clavePrivada = null): string
    {
        $datos = array_merge([
            'iss' => self::EMISOR,
            'aud' => self::AUDIENCIA,
            'iat' => time(),
            'exp' => time() + 60,
            'jti' => 'abc-123',
            'email' => 'ana@clinica.com',
            'documento' => '1001',
        ], $cambios);

        $cabecera = $this->base64Url(json_encode(['alg' => $alg, 'typ' => 'JWT']));
        $contenido = $this->base64Url(json_encode($datos));

        openssl_sign("{$cabecera}.{$contenido}", $firma, $clavePrivada ?? self::$privada, OPENSSL_ALGO_SHA256);

        return "{$cabecera}.{$contenido}." . $this->base64Url($firma);
    }

    public function test_acepta_un_token_valido(): void
    {
        $datos = $this->servicio()->validar($this->token());

        $this->assertSame('1001', $datos['documento']);
        $this->assertSame('abc-123', $datos['jti']);
    }

    public function test_rechaza_un_token_vencido(): void
    {
        $this->expectException(RuntimeException::class);
        $this->servicio()->validar($this->token(['iat' => time() - 500, 'exp' => time() - 300]));
    }

    public function test_rechaza_un_token_emitido_por_un_reloj_adelantado_mas_alla_del_margen(): void
    {
        $this->expectException(RuntimeException::class);
        $this->servicio(30)->validar($this->token(['iat' => time() + 45, 'exp' => time() + 105]));
    }

    public function test_acepta_el_mismo_token_si_el_margen_de_reloj_es_mayor(): void
    {
        $datos = $this->servicio(60)->validar($this->token(['iat' => time() + 45, 'exp' => time() + 105]));

        $this->assertSame('1001', $datos['documento']);
    }

    public function test_el_margen_de_reloj_no_vuelve_valido_un_token_realmente_vencido(): void
    {
        $this->expectException(RuntimeException::class);
        $this->servicio(60)->validar($this->token(['iat' => time() - 500, 'exp' => time() - 300]));
    }

    public function test_rechaza_un_token_de_otro_aplicativo(): void
    {
        $this->expectException(RuntimeException::class);
        $this->servicio()->validar($this->token(['aud' => 'Eventos']));
    }

    public function test_rechaza_un_emisor_desconocido(): void
    {
        $this->expectException(RuntimeException::class);
        $this->servicio()->validar($this->token(['iss' => 'https://otro.test']));
    }

    public function test_rechaza_un_token_modificado(): void
    {
        [$cabecera, , $firma] = explode('.', $this->token());
        $falso = $this->base64Url(json_encode([
            'iss' => self::EMISOR, 'aud' => self::AUDIENCIA, 'iat' => time(), 'exp' => time() + 60,
            'jti' => 'x', 'documento' => '9999',
        ]));

        $this->expectException(RuntimeException::class);
        $this->servicio()->validar("{$cabecera}.{$falso}.{$firma}");
    }

    public function test_rechaza_un_token_firmado_con_otra_clave(): void
    {
        [$otraPrivada] = self::generarClaves();

        $this->expectException(RuntimeException::class);
        $this->servicio()->validar($this->token([], 'RS256', $otraPrivada));
    }

    public function test_rechaza_algoritmos_distintos_de_rs256(): void
    {
        $this->expectException(RuntimeException::class);
        $this->servicio()->validar($this->token([], 'none'));
    }

    public function test_rechaza_basura(): void
    {
        $this->expectException(RuntimeException::class);
        $this->servicio()->validar('no-es-un-token');
    }
}
