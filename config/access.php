<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Access Map (Module/Ability -> Allowed Levels)
    |--------------------------------------------------------------------------
    | Single source of truth for access control.
    | Use with middleware: ->middleware('checkAccess:users.manage')
    */
    'map' => [
        // Super admin / admin
        'users.manage' => ['Direktur', 'Programmer', 'Kabag'],

        // Rekap & laporan
        'rekap.view' => ['Direktur', 'Programmer', 'HRD', 'Kabag', 'Kasie'],
    ],
];

