<?php

use Bernskiold\LaravelCollaboration\Models\Share;
use Bernskiold\LaravelCollaboration\Notifications\SharedWithYou;
use Bernskiold\LaravelCollaboration\Support\PeopleDirectory;

return [

    /*
     * The user model. Users of this model share records with other users.
     * If you set null, the package uses the user model of the default auth
     * provider (config('auth.providers.users.model')).
     */
    'user_model' => null,

    /*
     * The model and the table that keep the shares. To use a different
     * model, extend the Share model of the package.
     */
    'share_model' => Share::class,

    'table' => 'shares',

    /*
     * The database connection of the shares table. If you set null, the
     * package uses the default connection.
     */
    'connection' => null,

    /*
     * The class that finds the people for a share, and gives their names.
     * To change the search, extend the PeopleDirectory class. To invite
     * people by email address, also extend the PeopleDirectory class.
     */
    'people' => PeopleDirectory::class,

    'notifications' => [

        /*
         * If true, the package sends a notification to each person that
         * gets access to a record.
         */
        'enabled' => true,

        /*
         * The package sends this notification if a record does not override
         * shareNotification(). If you set null, the package sends no
         * notification. The constructor gets the record, the person that
         * shared it and the permission.
         */
        'default' => SharedWithYou::class,

    ],

    'activity_log' => [

        /*
         * If true, the package records each change to the shares of a
         * record in the activity log of that record. This function
         * operates only if spatie/laravel-activitylog is installed.
         */
        'enabled' => true,

        /*
         * The log name of the entries. If you set null, the package uses
         * the morph class of the shared record.
         */
        'log_name' => null,

    ],

];
