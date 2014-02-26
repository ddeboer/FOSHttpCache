<?php

// The application listens to HEAD requests and creates an X-Hash based on
// parameters in the request. In this case (vary by user role), it's based
// on Cookie.
if ('HEAD' == strtoupper($_SERVER['REQUEST_METHOD'])) {
    // Base user hash on the cookie.
    // Of course, application logic to determine hash could be way more complex
    // and useful.
    header(sprintf('X-Hash: %s', $_COOKIE[0]));
    exit;
}

// Handle normal GET requests
header('Cache-Control: max-age=3600');

switch ($_SERVER['HTTP_X_HASH']) {
    case 'role-1':
        echo 'Content for role 1';
        break;
    case 'role-2':
        echo 'Content for role 2';
}