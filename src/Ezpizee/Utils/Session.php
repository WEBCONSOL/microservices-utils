<?php

namespace Ezpizee\Utils;

class Session {

    private $cookieTime;

    public function __construct(int $expireDays=365) {
        if (!isset($_SESSION)) {
            if (defined('SESSION_DIR')) {
                session_save_path(SESSION_DIR);
            }
            else if (defined('TMP_DIR')) {
                session_save_path(TMP_DIR);
            }
            else if (defined('ROOT_DIR')) {
                session_save_path(ROOT_DIR.DIRECTORY_SEPARATOR.'tmp');
            }
            session_start();
        }
        $this->cookieTime = strtotime('+'.$expireDays.' days');
    }

    /**
     * @param $name
     * @param $value
     */
    public function set($name, $value) {
        $_SESSION[$name] = $value;
    }

    /**
     * @param $base
     * @param $key
     * @param $value
     */
    public function setMulti($base, $key, $value) {
        $_SESSION[$base][$key] = $value;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function get($name) {
        return isset($_SESSION) && isset($_SESSION[$name]) ? $_SESSION[$name] : null;
    }

    /**
     * @param $base
     * @param $key
     * @return mixed
     */
    public function getMulti($base, $key) {
        return isset($_SESSION) && isset($_SESSION[$base]) && isset($_SESSION[$base][$key]) ? $_SESSION[$base][$key] : null;
    }

    /**
     * @param $name
     */
    public function kill($name) {
        unset($_SESSION[$name]);
    }

    /**
     * Destroy session
     */
    public function killAll() {
        session_destroy();
    }

    /**
     * @param $name
     * @param $value
     */
    public function setCookie($name, $value, $expire=0, $path='/') {
        setcookie($name, $value, $expire > 0 ? $expire : $this->cookieTime, $path);
    }

    /**
     * @param $name
     * @return mixed
     */
    public function getCookie($name) {
        return isset($_COOKIE) && isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
    }

    /**
     * @param $name
     */
    public function killCookie($name) {
        setcookie($name, null);
    }
}