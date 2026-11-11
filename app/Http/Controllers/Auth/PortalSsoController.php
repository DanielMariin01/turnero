<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PortalSsoService;
use App\Support\AreaDeTrabajo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Ingreso desde el Portal Corporativo: valida el pase, busca al usuario por cédula, comprueba que pueda
 * trabajar en el área elegida y abre la sesión mostrándole solo las pantallas de esa área.
 */
class PortalSsoController extends Controller
{
    /** Tiempo que se recuerda un pase usado; debe superar su duración (60 s) más el margen de reloj. */
    private const SEGUNDOS_PASE_USADO = 180;

    public function __invoke(Request $request): RedirectResponse
    {
        try {
            $datos = $this->servicio()->validar((string) $request->query('token'));
        } catch (RuntimeException) {
            return $this->rechazar('El ingreso desde el portal no es válido o venció. Vuelve a abrirlo desde el portal.');
        }

        if (! $this->marcarComoUsado($datos['jti'])) {
            return $this->rechazar('Este enlace de ingreso ya fue utilizado. Vuelve a abrirlo desde el portal.');
        }

        $area = $datos['area'] ?? null;
        if (! AreaDeTrabajo::existe($area)) {
            return $this->devolverAlPortal('area_no_valida');
        }

        $usuarios = User::where('cedula', (string) $datos['documento'])->limit(2)->get();
        if ($usuarios->count() !== 1) {
            return $this->devolverAlPortal('documento_no_registrado', $area);
        }

        /** @var User $usuario */
        $usuario = $usuarios->first();

        if (! $usuario->activo) {
            return $this->devolverAlPortal('usuario_inactivo', $area);
        }

        if (! AreaDeTrabajo::habilita($usuario->getRoleNames()->all(), $area)) {
            return $this->devolverAlPortal('sin_permiso_area', $area);
        }

        Auth::login($usuario);
        $request->session()->regenerate();
        $request->session()->put(AreaDeTrabajo::SESION, $area);

        return redirect()->to($this->pantallaDeInicio($area));
    }

    private function servicio(): PortalSsoService
    {
        $ruta = config('services.portal.clave_publica');

        return new PortalSsoService(
            is_file($ruta) ? (string) file_get_contents($ruta) : '',
            (string) config('services.portal.url'),
            (string) config('services.portal.audiencia'),
        );
    }

    /** Un pase solo sirve una vez. */
    private function marcarComoUsado(string $jti): bool
    {
        return Cache::store('file')->add("sso-jti-{$jti}", true, self::SEGUNDOS_PASE_USADO);
    }

    /** Primera pantalla del área que este usuario puede ver; si no hay, el panel. */
    private function pantallaDeInicio(string $area): string
    {
        foreach (AreaDeTrabajo::pantallasDe($area) as $pantalla) {
            if ($pantalla::canViewAny()) {
                return $pantalla::getUrl();
            }
        }

        return url('/admin');
    }

    /** El portal muestra el aviso (pop-up): allí el usuario ve qué datos tiene registrados. */
    private function devolverAlPortal(string $motivo, ?string $area = null): RedirectResponse
    {
        $consulta = http_build_query(array_filter([
            'sso_error' => $motivo,
            'sso_app' => config('services.portal.audiencia'),
            'sso_area' => $area,
        ]));

        return redirect()->away(rtrim((string) config('services.portal.url'), '/') . '/?' . $consulta);
    }

    private function rechazar(string $mensaje): RedirectResponse
    {
        return redirect()->to(url('/admin/login'))->withErrors(['email' => $mensaje]);
    }
}
