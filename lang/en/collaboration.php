<?php

return [

    'permissions' => [
        'view' => [
            'label' => 'Can view',
            'description' => 'Can open it, but cannot change it.',
        ],
        'edit' => [
            'label' => 'Can edit',
            'description' => 'Can open it and change it.',
        ],
    ],

    'notification' => [
        'subject' => ':person shared :name with you',
        'subject_anonymous' => ':name is shared with you',
        'line' => 'You can now open :name.',
        'action' => 'Open :name',
    ],

    'activity' => [
        'shared' => 'shared it with',
        'unshared' => 'removed access for',
        'changed' => 'changed access for',
        'and_more' => ':names and :count more',
    ],

];
