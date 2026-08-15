<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Página de erro com a cara do painel para respostas HTTP que fazem
        // parte do fluxo normal (403/404/419/429) — não são bug, então não
        // precisam de stack trace, sempre estilizadas. 500/503 só usam a
        // página estilizada fora de local/testing; em dev continuamos com o
        // Whoops (APP_DEBUG) para ver o stack trace de verdade.
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return null;
            }

            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

            $semprEstilizado = in_array($status, [403, 404, 419, 429], true);
            // Com APP_DEBUG=true (mesmo em produção), deixa o Whoops mostrar
            // o stack trace de verdade em vez de esconder atrás da página
            // estilizada — senão fica impossível depurar um 500 remoto.
            $so500ForaDeDev = $status >= 500 && ! app()->environment(['local', 'testing']) && ! config('app.debug');

            if (! $semprEstilizado && ! $so500ForaDeDev) {
                return null;
            }

            return Inertia::render('Error', ['status' => $status])
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })->create();
