<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cgit_auth
{

    /**
     * Current user object / or FALSE
     *
     * @var mixed
     */
    public $user = FALSE;

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Constructor
     *
     * Checks if a user is currently logged in, validates the user and updates their last action.
     *
     * @access  public
     * @return  void
     */
    public function __construct()
    {
        // Load
        $this->load->library('session');
        $this->load->config('cgit_auth');
        $this->load->model('cgit_auth_model');
        $this->lang->load('cgit_auth');

        // Does the user have a remember me cookie and require a persistent login?
        if ($this->cgit_auth_model->has_persistent_login())
        {
            // Log the user in via cookie;
            $this->_login_persistent();
            
            // We do not need to check for existing session after this
            return;
        }

        // Does it look like a user is logged in?
        if ($this->session->userdata($this->config->item('session_namespace')) !== FALSE)
        {
            
            // Validate the user exists and set the user data
            if ($this->_set_current_user($this->session->userdata($this->config->item('session_namespace'))))
            {
                // User is valid so update their last action
                $this->_update_last_action($this->session->userdata($this->config->item('session_namespace')));
            }
        }
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

   /**
    * Get
    *
    * Get the instance of the framework without having to set an extra variable.
    *
    * @access   public
    * @param    mixed
    * @return   object
    */
    public function __get($var)
    {
        return get_instance()->$var;
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------
    
    /**
     * Login
     *
     * Allows a user to authenticate with the website. This function returns an error message if the details are invalid, the account is suspended or has 
     * yet to be activated. On success the function returns true.
     *
     * @access  public
     * @param   string
     * @param   string
     * @param   string      Remember this user and log them in automatically
     * @return  mixed       Integer on success, String on failure
     */
    public function login($username = NULL, $password = NULL, $remember = FALSE)
    {
        if (!is_null($username) && !is_null($password))
        {
            // Is the account valid?
            if ($user = $this->cgit_auth_model->login($username, $password))
            {
                // Is the account activated?
                if ($user->active == 1)
                {
                    if ($user->suspended == 0)
                    {
                        $this->cgit_auth_model->set_last_login($user->id);
                        $this->_set_current_user($user->id);

                        // User would like to remember login details for this computer
                        if ($remember)
                        {
                            $this->cgit_auth_model->set_remember_cookie($user->id);
                        }
                        
                        // Return the user type ID
                        return (int)$user->id;

                    }

                    return $this->lang->line('login_suspended');

                }

                return $this->lang->line('login_inactive');
            }
        }

        return $this->lang->line('login_unsuccessful');
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Sign up
     *
     * Creates a new user record.
     *
     * @access  public
     * @param   string
     * @param   string
     * @param   string
     * @param   string
     * @return  boolean
     */
    public function sign_up($username, $password, $first_name, $last_name)
    {
        // Check a user does not exist with the current email address
        if ($this->cgit_auth_model->get_by_email($username))
        {
            return FALSE;
        }
        
        // Run the signup process
        return $this->cgit_auth_model->sign_up($username, $password, $first_name, $last_name);
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Send activation
     *
     * Sends an activation link for specific user
     *
     * @access  public
     * @param   integer     User ID
     * @return  boolean
     */
    public function send_activation($user_id)
    {
        // To finish
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Activate
     *
     * Checks for a user record with a specific activation token
     *
     * @access  public
     * @param   string      Activation token
     * @return  boolean     Returns values on invalid activation code, and true when a valid user is valid user is found for that code
     */
    public function activate($activation_token)
    {
        if ($this->cgit_auth_model->get_by_activation($activation_token))
        {
            $this->cgit_auth_model->activate($activation_token);
            return TRUE;
        }

        return FALSE;
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Login persistent
     *
     * Logs a user in using their remember me cookie details
     *
     * @access  private
     * @return  boolean
     */
    private function _login_persistent()
    {
        // Does the cookie exist and is it valid?
        if ($cookie = $this->cgit_auth_model->get_persistent_login())
        {
            // Login the user
            $this->_set_current_user($cookie['user_id'], TRUE);

            // Update their last action
            $this->_update_last_action($cookie['user_id']);
            
            // Renew remember me cookie
            $this->cgit_auth_model->renew_remember_cookie($cookie['user_id'], $cookie['token']);

            return TRUE;
        }

        return FALSE;
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Logout
     *
     * Log a user out, unset their user details and close the session.
     *
     * @access  public
     * @param   string
     * @param   string
     * @return  boolean
     */
    public function logout()
    {
        // Unset
        $this->session->unset_userdata($this->config->item('session_namespace'));
        $this->user = FALSE;
        $this->session->sess_destroy();
        $this->cgit_auth_model->remove_remember_cookie();
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Logged in
     *
     * Checks if a user is currently logged in.
     *
     * @access  public
     * @return  boolean
     */
    public function logged_in()
    {
        if ($this->user && is_object($this->user))
        {
            return TRUE;
        }

        return FALSE;
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Logged in via cookie
     *
     * Checks if a user is currently logged in via cookie or normal authentication.
     *
     * @access  public
     * @return  boolean
     */
    public function logged_in_via_cookie()
    {
        return $this->logged_in() ? (isset($this->user->cookie) && $this->user->cookie) : FALSE;
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Set current user
     *
     * Checks that the user exists and sets the current user details if they are active and not suspended.
     *
     * @access  private
     * @param   integer
     * @return  boolean
     */
    private function _set_current_user($user_id, $cookie = FALSE)
    {
        // Check the user exists
        if (($user = $this->cgit_auth_model->get($user_id)) && $user->active == 1 && $user->suspended == 0)
        {
            // Set the session user identifier
            $this->session->set_userdata($this->config->item('session_namespace'), $user_id);

            // Set the user data
            $this->user = $user;
            
            // Mark as logged in via cookie or not
            $this->user->cookie = $cookie;

            return TRUE;
        }

        // The user was not found, set it false
        $this->user = FALSE;

        return FALSE;
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Update last action
     *
     * Updates the last action record for the user .
     *
     * @access  private
     * @param   integer
     * @return  void
     */
    private function _update_last_action($user_id)
    {
        $this->cgit_auth_model->set_last_action($user_id);
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

}
/* End of file cgit_auth.php */
/* Location: ./application/libraries/cgit_auth.php */