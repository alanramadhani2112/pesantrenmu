<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SSO Enabled
    |--------------------------------------------------------------------------
    |
    | Determines whether SSO login via Muhammadiyah ID is enabled.
    | When disabled, the SSO login button will be hidden from the login page.
    |
     */
    'enabled' => env('SSO_ENABLED', false),

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
    | M-3 fix: tidak ada default value — jika SSO_CLIENT_ID tidak di-set,
    | config akan null dan request ke IdP akan gagal dengan error yang jelas.
    |
     */
    'client_id' => env('SSO_CLIENT_ID'),

    /*
    |--------------------------------------------------------------------------
    | SSO Client Secret
    |--------------------------------------------------------------------------
    | 
    | Contains the secret for authenticating SSO request.
    | M-3 fix: tidak ada default value — jika SSO_CLIENT_SECRET tidak di-set,
    | config akan null. Jangan pernah commit secret ke source code.
    |
     */
    'client_secret' => env('SSO_CLIENT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Redirect URL
    |--------------------------------------------------------------------------
    | 
    | Contains the the redirect url after successful login.
    |
     */

     'redirect_url' => '/dashboard',

    /*
    |--------------------------------------------------------------------------
    | SSO Redirect URI (OAuth Callback)
    |--------------------------------------------------------------------------
    |
    | The OAuth redirect URI used during the SSO authorization flow.
    | If set, this overrides the default route('sso.callback') URL.
    | Useful when the app is behind a reverse proxy or custom domain.
    |
     */
    'redirect_uri' => env('SSO_REDIRECT_URI', null),

    /*
    |--------------------------------------------------------------------------
    | SSO Scopes
    |--------------------------------------------------------------------------
    |
    | The OAuth scopes to request from the SSO provider.
    |
     */
    'scopes' => env('SSO_SCOPES', 'openid profile email'),

    /*
    |--------------------------------------------------------------------------
    | SSO Timeout
    |--------------------------------------------------------------------------
    |
    | The HTTP request timeout (in seconds) for SSO server communication.
    | If the SSO server does not respond within this time, the request
    | will be aborted and the user redirected back with an error.
    |
     */
    'timeout' => env('SSO_TIMEOUT', 10),
];
