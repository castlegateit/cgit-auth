<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// ---------------------------------------------------------------------------------------------------------------------------------------------------------

/**
 * BCRYPT Iterations
 *
 * Number of BCRYPT itererations to perform when hashing passwords. Changing this will invalidate any existing passwords.
 */
$config['bcrypt_iterations'] = 12;

// ---------------------------------------------------------------------------------------------------------------------------------------------------------

/**
 * Session namespace
 *
 * Name of the session variable to store authentication information.
 */
$config['session_namespace'] = '__cgit_auth';

// ---------------------------------------------------------------------------------------------------------------------------------------------------------

/**
 * Persistent cookie name
 *
 * The name of the cookie used to store persistent login information.
 */
$config['persistent_cookie_name'] = 'cgit_auth_p';

// ---------------------------------------------------------------------------------------------------------------------------------------------------------

/**
 * Persistent cookie secure
 *
 * Whether the persistent cookie secure. Use when the website is running on SSL
 */
$config['persistent_cookie_secure'] = FALSE;

// ---------------------------------------------------------------------------------------------------------------------------------------------------------

/**
 * Persistent login expiry
 *
 * Expiry time of persistent login cookies in seconds
 */
$config['persistent_login_expiry'] = 7889238; // 3 months

// ---------------------------------------------------------------------------------------------------------------------------------------------------------

/**
 * Persistent token salt
 *
 * Salt to use on token hashes. Change this to your own value
 */
$config['persistent_token_salt'] = 'J7%2Smd!2sP[}';

// ---------------------------------------------------------------------------------------------------------------------------------------------------------

/* End of file cgit_auth.php */
/* Location: ./application/config/cgit_auth.php */
