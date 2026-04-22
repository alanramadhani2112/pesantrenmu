<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SSO Server
    |--------------------------------------------------------------------------
    | 
    | Contains the base url for requesting SSO, or you can called it
    | parent application.
    |
     */
    'server_url' => env('SSO_SERVER_URL', 'http://localhost:8000'),

    /*
    |--------------------------------------------------------------------------
    | SSO Client ID
    |--------------------------------------------------------------------------
    | 
    | Contains the client for authenticating SSO request.
    |
     */
    'client_id' => env('SSO_CLIENT_ID', 'someId'),

    /*
    |--------------------------------------------------------------------------
    | SSO Client Secret
    |--------------------------------------------------------------------------
    | 
    | Contains the secret for authenticating SSO request.
    |
     */
    'client_secret' => env('SSO_CLIENT_SECRET', 'secretKey'),

    /*
    |--------------------------------------------------------------------------
    | Redirect URL
    |--------------------------------------------------------------------------
    | 
    | Contains the the redirect url after successful login.
    |
     */

     'redirect_url' => '/dashboard'
];
