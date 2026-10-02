<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#1E3A2C">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <title>Entrar — Painel — {{ $clinica['nome'] }}</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="relative flex min-h-screen overflow-x-hidden bg-verde px-6 py-12 font-sans text-texto antialiased">
    <x-ui.ramo tom="folha" :folhas="14" contorno class="pointer-events-none absolute -bottom-24 -left-16 h-[34rem] w-auto -rotate-12 opacity-30" />
    <x-ui.ramo tom="terracota" :folhas="9" contorno class="pointer-events-none absolute -top-16 -right-10 h-80 w-auto rotate-[200deg] opacity-40" />

    <main id="conteudo-principal" class="relative m-auto w-full max-w-sm">
        <div class="mb-8 text-center">
            <x-ui.logo sobre-escuro class="h-16" />
            <h1 class="mt-4 font-serif text-2xl text-white">Painel administrativo</h1>
            <p class="mt-1 text-sm text-creme/70">{{ $clinica['nome'] }}</p>
        </div>

        <div class="rounded-[1.75rem] border border-white/10 bg-creme p-6 shadow-2xl sm:p-8">
            <form method="POST" action="{{ route('admin.login.store') }}" novalidate>
                @csrf

                <div>
                    <label for="email" class="text-sm font-medium text-texto">E-mail</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                           autocomplete="username" autofocus required aria-required="true"
                           @if ($errors->has('email')) aria-invalid="true" aria-describedby="erro-email" @endif
                           class="campo mt-1.5">
                    @error('email')
                        <p id="erro-email" class="mt-1.5 text-xs font-medium text-red-700" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-5">
                    <label for="password" class="text-sm font-medium text-texto">Senha</label>
                    <input type="password" id="password" name="password"
                           autocomplete="current-password" required aria-required="true"
                           @if ($errors->has('password')) aria-invalid="true" aria-describedby="erro-password" @endif
                           class="campo mt-1.5">
                    @error('password')
                        <p id="erro-password" class="mt-1.5 text-xs font-medium text-red-700" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn-primario mt-7 w-full">Entrar</button>
            </form>
        </div>

        <p class="mt-6 text-center text-xs text-creme/60">
            <a href="{{ url('/') }}" class="underline underline-offset-2 hover:text-creme">Voltar ao site</a>
        </p>

        <x-admin.rodape escuro class="mt-10 !items-center border-t border-white/10 pt-5 text-center sm:!flex-col" />
    </main>
</body>
</html>
