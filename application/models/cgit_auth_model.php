<?php

class Cgit_auth_model extends CI_Model
{

    /**
     * phpass class instance
     */
    private $phpass;

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Constructor
     *
     * Perform startup actions.
     *
     * @access  public
     * @return  void
     */
    public function __construct()
    {
        // Load
        $this->load->helper('cookie');
        $this->load->config('cgit_auth');

        // Load BCRYPT library
        require_once(APPPATH . '/libraries/phpass/PasswordHash.php');
        $this->phpass = new PasswordHash($this->config->item('bcrypt_iterations'), FALSE);
        
        // Cleanup expired persistent logins
        $this->_clean_persistent_logins();
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Login
     *
     * Perform startup actions.
     *
     * @access  public
     * @param   string
     * @param   string
     * @return  mixed       Object on success, False on failure
     */
    public function login($username, $password)
    {
        // Get user by username
        $query = $this->db->from('users')
            ->where(array('email' => $username))
            ->get();

        if ($query->num_rows() == 1 && $this->compare_password($password, $query->row()->password))
        {
            return $query->row();
        }

        return FALSE;
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Sign up
     *
     * Register a new user. Account are inactive by default and must be activated seperately.
     *
     * @access  public
     * @param   string
     * @param   string
     * @return  mixed
     */
    public function sign_up($username, $password, $first_name, $last_name)
    {
        $query = $this->db->insert('users', array(
            'email'            => $username,
            'password'         => $this->phpass->HashPassword($password),
            'first_name'       => $first_name,
            'last_name'        => $last_name,
            'token'            => sha1('CGITauth' . microtime()),
            'active'           => 0,
            'date_created'     => date('Y-m-d H:i:s')
        ));
        return $this->db->insert_id();
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get
     *
     * Get a user record.
     *
     * @access  public
     * @param   integer
     * @return  mixed
     */
    public function get($user_id)
    {
        $query = $this->db->from('users')
            ->where(array('id' => $user_id))
            ->get();

        if ($query->num_rows() == 1)
        {
            return $query->row();
        }

        return FALSE;
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get by email
     *
     * Get a user record by email address.
     *
     * @access  public
     * @param   integer
     * @return  mixed
     */
    public function get_by_email($email)
    {
        $query = $this->db->select('email')
            ->from('users')
            ->where(array('email' => $email))
            ->get();

        if ($query->num_rows() == 1)
        {
            return $query->row();
        }

        return FALSE;
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get by activation
     *
     * Get a user record by activation token.
     *
     * @access  public
     * @param   integer
     * @return  mixed
     */
    public function get_by_activation($activation_token)
    {
        $query = $this->db->select('*')
            ->from('users')
            ->where(array('token' => $activation_token, 'active' => 0))
            ->get();

        if ($query->num_rows() == 1)
        {
            return $query->row();
        }

        return FALSE;
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Activate
     *
     * Activate a user by their activation token.
     *
     * @access  public
     * @param   string      Activation token
     * @return  void
     */  
    public function activate($activation_token)
    {
        $query = $this->db->where('token', $activation_token)
            ->update('users', array(
                'date_last_action' => date('Y-m-d H:i:s'),
                'active'           => 1
            ));
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Set last action
     *
     * Updates the last action timestamp for the user
     *
     * @access  public
     * @return  void
     */  
    public function set_last_action($user_id)
    {
        $query = $this->db->where('id', $user_id)
            ->update('users', array('date_last_action' => date('Y-m-d H:i:s')));
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Set last login
     *
     * Updates the last login timestamp for the user.
     *
     * @access  public
     * @return  void
     */  
    public function set_last_login($user_id)
    {
        $query = $this->db->where('id', $user_id)
            ->update('users', array('date_last_login' => date('Y-m-d H:i:s')));

    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Set remember me cookie
     *
     * Creates a new remember me cookie and saves a record in the database.
     *
     * @access  public
     * @param   integer
     * @return  void
     */  
    public function set_remember_cookie($user_id)
    {
        // Create the token
        $token = sha1(microtime() . $this->config->item('persistent_token_salt'));
        $token.= sha1($_SERVER['HTTP_USER_AGENT'] . $this->config->item('persistent_token_salt'));
        
        // Expiry date
        $expiry = time() + $this->config->item('persistent_login_expiry');

        // Create the remember cookie
        setcookie(
            $this->config->item('persistent_cookie_name'),
            $user_id . '-' . $token,
            $expiry,
            '/',
            $_SERVER['HTTP_HOST'],
            $this->config->item('persistent_login_secure'),
            TRUE
        );

        // Insert into the database
        $query = $this->db->insert(
            'persistent_logins',
            array('user_id' => $user_id, 'token_hash' => sha1($token . $this->config->item('persistent_token_salt')), 'expiry' => $expiry)
        );
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Clean persistent logins
     *
     * Removes the record of any persistent logins in the database if the expiry date is in the past.
     *
     * @access  private
     * @return  void
     */
    private function _clean_persistent_logins()
    {
        // Remove from the database
        $this->db->query('DELETE FROM `persistent_logins` WHERE `expiry` < ' . time());
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Has persistent login
     *
     * Checks for the existance of a value persistent login cookie.
     *
     * @access  public
     * @return  boolean
     */
    public function has_persistent_login()
    {
        // Get cookie
        $cookie = $this->get_persistent_login();

        if ($cookie)
        {
            // Valid cookie is present
            return TRUE;
        }

        // Cookie is invalid - remove it
        //$this->remove_remember_cookie();
        return FALSE;
        
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Renew remember cookie
     *
     * Removes the existing remember cookie and creates a new on it its place.
     *
     * @access  public
     * @return  boolean
     */
    public function renew_remember_cookie($user_id, $token)
    {
        // Delete reference to existing cookie
        $this->db->query("DELETE FROM `persistent_logins` WHERE `user_id` = " . intval($user_id) . " AND `token_hash` = '" . 
            sha1($token . $this->config->item('persistent_token_salt')) . "'");

        // Create new remember cookie - the old one is overwritten
        $this->set_remember_cookie($user_id);
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Remove remember cookie
     *
     * Removes the existing remember cookie.
     *
     * @access  public
     * @return  boolean
     */
    public function remove_remember_cookie()
    {
        // Set the cookie with an expiry in the past
        setcookie(
            $this->config->item('persistent_cookie_name'),
            '',
            time() - 99999,
            '/',
            $_SERVER['HTTP_HOST'],
            $this->config->item('persistent_login_secure'),
            TRUE
        );
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get persisten login
     *
     * Attempts to get the persistent login cookie and returns the user id and token, or false on failure.
     *
     * @access  public
     * @return  mixed       array on success, false on failure
     */  
    public function get_persistent_login()
    {
        if (isset($_COOKIE[$this->config->item('persistent_cookie_name')]))
        {
            // Split at "-" to get user id and the token
            $parts = explode('-', $_COOKIE[$this->config->item('persistent_cookie_name')]);
            if (count($parts) == 2)
            {
                $user_id = $parts[0];
                $token   = $parts[1];

                // Check the user agent is valid
                if (substr($token, -40) != sha1($_SERVER['HTTP_USER_AGENT'] . $this->config->item('persistent_token_salt')))
                {
                    return FALSE;
                }

                // Check against the database
                $db_check = $this->db->get_where('persistent_logins', array('token_hash' => sha1($token . $this->config->item('persistent_token_salt'))));
                return ($db_check->num_rows() == 1) ? array('user_id' => $user_id, 'token' => $token) : FALSE;
            }
        }

        return FALSE;
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Compare password
     *
     * Compares the supplied password with a hashed password.
     *
     * @access  public
     * @return  boolean
     */  
    public function compare_password($password, $hashed_password)
    {
        return $this->phpass->CheckPassword($password, $hashed_password);
    } 

    // -----------------------------------------------------------------------------------------------------------------------------------------------------

}
/* End of file cgit_auth_model.php */
/* Location: ./application/models/cgit_auth_model.php */