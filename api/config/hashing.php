<?php

return [

    'driver' => 'argon2id',

    'argon' => [
        'memory' => 65536,
        'threads' => 4,
        'time' => 4,
        'verify' => true,
    ],

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
        'verify' => true,
    ],

    'rehash_on_login' => true,

];
