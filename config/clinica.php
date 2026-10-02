<?php

/*
|--------------------------------------------------------------------------
| Dados públicos da Dra. Gabriela Ferri Paes
|--------------------------------------------------------------------------
|
| Única fonte dos dados institucionais exibidos no site — todos confirmados
| pela cliente. Não acrescentar nada aqui que não tenha sido informado por
| ela (formação, especializações, convênios, horários exatos etc.).
|
*/

return [
    'nome' => 'Dra. Gabriela Ferri Paes',
    'profissao' => 'Cirurgiã-Dentista',
    'cro' => 'CRO-SP 113798',

    'whatsapp_exibicao' => '+55 11 97153-4002',
    'whatsapp_numero' => '5511971534002',
    'whatsapp_mensagem' => 'Olá, Dra. Gabriela! Vim pelo site e gostaria de agendar uma consulta.',

    'email' => 'dra.gabrielaferripaes@gmail.com',
    'instagram_url' => 'https://www.instagram.com/dra.gabiferripaes/',
    'instagram_usuario' => '@dra.gabiferripaes',

    'endereco' => [
        'logradouro' => 'R. Biotônico, 1185',
        'bairro' => 'Vila Colorado',
        'cidade' => 'Suzano - SP',
        'cep' => '08616-040',
    ],

    // Somente o que foi confirmado. Os horários exatos usados pela agenda
    // são provisórios e configuráveis no painel — nunca exibidos como
    // horário oficial no site público.
    'atendimento' => 'Segunda a sábado, somente com hora marcada.',
    'atendimento_domingo' => 'Domingo: consultório fechado.',

    // Identificação discreta exibida no rodapé e no painel.
    'demo' => (bool) env('DEMO_MODE', true),

    // Credenciais do painel: SOMENTE via variáveis de ambiente — nunca
    // escritas em código. Lidas pelo AdminUserSeeder (lido via config para
    // continuar funcionando com "php artisan config:cache" em produção).
    'admin' => [
        'nome' => env('ADMIN_NAME', 'Dra. Gabriela Ferri Paes'),
        'email' => env('ADMIN_EMAIL'),
        'senha' => env('ADMIN_PASSWORD'),
    ],
];
