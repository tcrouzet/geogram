<?php

return [
    'domain' => 'accounts.google.com', // Le domaine de Google pour OAuth
    'clientId' => '',
    'clientSecret' => '',
    'redirectUri' => 'https://geo.zefal.com/api/callback',
    'cookie_secret' => '' // Par exemple, généré avec bin2hex(random_bytes(32))
];