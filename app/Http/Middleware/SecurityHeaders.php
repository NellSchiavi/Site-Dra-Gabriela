<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Headers de segurança aplicados a toda resposta do grupo "web"
 * (site público + painel administrativo). Nenhum destes headers depende de
 * infraestrutura externa — só declara ao navegador comportamentos mais
 * seguros que ele já suporta nativamente.
 *
 * CSP: todo o front-end é self-hosted via Vite (JS/CSS/fontes — sem CDN, sem
 * Google Fonts externo, sem imagem externa), então font-src/img-src/connect-src
 * ficam restritos a 'self'. A única origem externa é o iframe do Google Maps
 * (frame-src). script-src e style-src precisaram de duas
 * concessões pontuais, ambas exigidas pelo Alpine.js (build padrão, não a
 * build @alpinejs/csp) — sem elas a aplicação inteira para de funcionar:
 *
 *  - 'unsafe-eval' em script-src: Alpine avalia expressões de x-data/x-show/
 *    @click/etc. via `new Function()`.
 *  - 'unsafe-inline' em style-src: x-show/x-transition (usados até no
 *    header público, presente em toda página) escondem/mostram elementos
 *    escrevendo direto em `element.style.display`/propriedades de transição
 *    via JS — CSP trata isso como "inline style" mesmo não vindo de nenhum
 *    atributo style="" no HTML-fonte, e não existe hash/nonce que cubra
 *    valor calculado em runtime.
 *
 * Migrar para a build CSP do Alpine (registrar tudo via Alpine.data() e
 * reescrever x-show/x-transition/@click em várias views) é uma refatoração
 * grande, desnecessária para a demo. Nenhuma das duas exceções permite executar um <script> ou
 * <style> injetado por payload de XSS refletido: script-src/style-src ainda
 * exigem 'self' para a ORIGEM do recurso, as exceções só liberam que o
 * bundle JÁ confiável (nosso próprio) manipule eval/estilo internamente.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        return $this->aplicar($next($request));
    }

    /**
     * Extraído de handle() para poder ser reaproveitado por
     * bootstrap/app.php (Exceptions::respond()) — uma resposta construída a
     * partir de uma exceção (redirect de guest/auth, 404, 419, 500...) é
     * montada FORA do pipeline normal de middleware, então o "depois do
     * $next()" deste middleware nunca roda para ela. Sem esse segundo ponto
     * de aplicação, páginas de erro ficariam sem os headers de segurança.
     */
    public function aplicar(Response $response): Response
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // Demo temporária: não indexar nada. NÃO é mecanismo de segurança —
        // só evita que a demo apareça em buscadores.
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        if (app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data:",
            "font-src 'self'",
            "connect-src 'self'",
            // Única origem externa: o mapa incorporado da seção Localização.
            "frame-src https://www.google.com https://maps.google.com",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
        ]));

        return $response;
    }
}
