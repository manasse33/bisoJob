<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration optimisée pour votre API Laravel utilisant les Tokens (Sanctum)
    | et hébergée sur Railway/Vercel.
    |
    */

    // 💡 Changement ici : Suppression de 'sanctum/csrf-cookie'. 
    // On ne cible que les routes API.
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // 💡 Nous gardons l'origine exacte de votre frontend. C'est correct.
    'allowed_origins' => ['https://bisojob.vercel.app'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // 💡 Laisser à 'false' puisque vous utilisez des Tokens Bearer et non des sessions/cookies.
    'supports_credentials' => false,

];
