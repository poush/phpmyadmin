<?php

namespace PMA\libraries;

class Cookie {

    public static $cookies;
    
    public static $httponly;
    
    public static $cookieValidities;


    /**
     * removes cookie
     *
     * @param string $cookie name of cookie to remove
     * @param int    $validity validity of cookie in seconds (default is one month)
     *
     * @return boolean result of setcookie()
     */
    public static function removeCookie($cookie,$validity=null)
    {

        
        $file = fopen('log.txt', 'a') or die('Cannot open file');

        $parentCookie = self::getCookieParent($cookie);

        $cookieData = json_decode(
                                isset(self::$cookies[$parentCookie])?
                                self::$cookies[$parentCookie]:'{}'
                        , true);


        // Removing child cookie
        if(isset($cookieData[$parentCookie]))
            unset($cookieData[$cookie]);

        if (defined('TESTSUITE')) {
            if(sizeof($cookieData))
                $_COOKIE[$parentCookie] = json_encode($cookieData);
            else if(isset($_COOKIE[$parentCookie]))
                // if no chld is cookie is set then
                //  remove the parent index from cookie
                unset($_COOKIE[$parentCookie]);
            return true;
        }
        // Considering parent cookie is blank
        $time = time()- 3600;

        /* Calculate cookie validity if parent cookie is not blank */
        if(sizeof($cookieData))
        {   
            if ($validity === null) {
                $time = time() + 2592000;
            } elseif ($validity == 0) {
                $time = 0;
            } else{
                $time = time() + $validity;
            } 
        }
        self::$cookies[$parentCookie] = json_encode($cookieData);

    }

    /**
     * sets cookie if value is different from current cookie value,
     * or removes if value is equal to default
     *
     * @param string $cookie   name of cookie to remove
     * @param mixed  $value    new cookie value
     * @param string $default  default value
     * @param int    $validity validity of cookie in seconds (default is one month)
     * @param bool   $httponly whether cookie is only for HTTP (and not for scripts)
     *
     * @return boolean result of setcookie()
     */
    public static function setCookie($cookie, $value, $default = null,
        $validity = null, $httponly = true
    ) {

        self::$httponly = $httponly;
        $parentCookie = self::getCookieParent($cookie);
        $cookieData = json_decode(
                                isset(self::$cookies[$parentCookie])?
                                self::$cookies[$parentCookie]:'{}'
                        , true);

        // if($cookie == 'pma_fontsize')
        //     // die(var_dump(self::$cookies[$parentCookie]));
        // if (mb_strlen($value) && null !== $default && $value === $default
        // ) {

        //     // default value is used
        //     if (isset($cookieData[$cookie])) {
        //         // remove cookie
        //         unset($cookieData[$cookie]);
        //     }
        // }
        // else 
            if (!mb_strlen($value) && isset($cookieData[$cookie])) {
                // remove cookie, value is empty
                unset($cookieData[$cookie]);
            }

        elseif (!isset($cookieData[$cookie]) 
              || $cookieData[$cookie] !== $value) {
                //set cookie

                $cookieData[$cookie] = $value;
        }
        
        //Considering that parent cookie is blank            
        $time = time() - 3600;
            
        /* Calculate cookie validity if parent cookie is not blank */
        if(sizeof($cookieData))
        {   
            if ($validity === null) {
                $time = time() + 2592000;
            } elseif ($validity == 0) {
                $time = 0;
            } else{
                $time = time() + $validity;
            } 
        }
        
        if (defined('TESTSUITE')) {
            if(sizeof($cookieData))
                $_COOKIE[$parentCookie] = json_encode($cookieData);
            else if(isset($_COOKIE[$parentCookie]))
                // if cookieJSon in blank remote the index from cookie
                unset($_COOKIE[$parentCookie]);
            return true;
        }

        self::$cookies[$parentCookie] = json_encode($cookieData);
        self::$cookieValidities[$parentCookie] = $time;

        // if(mb_strlen($value))
        //     self::sendCookies();
    }

    public static function sendCookies()
    {

        if(isset(self::$cookies['pmaUser-N']))
             setcookie(
                'pmaUser-N',
                self::$cookies['pmaUser-N'],
                self::$cookieValidities['pmaUser-N'],
                '/',
                '',
                '',
                self::$httponly
            );
        if(isset(self::$cookies['pmaAuth-N']))
             setcookie(
                'pmaAuth-N',
                self::$cookies['pmaAuth-N'],
                self::$cookieValidities['pmaAuth-N'],
                '/',
                '',
                '',
                self::$httponly
            );
        if(isset(self::$cookies['pmaConfig']))
             setcookie(
                'pmaConfig',
                self::$cookies['pmaConfig'],
                self::$cookieValidities['pmaConfig'],
                '/',
                '',
                '',
                self::$httponly
            );
    }
    

    /**
     *  Get cookie value 
     *
     * @param  string $cookie Name of cookie whose value is needed
     *
     * @return string Value of Cookie
     */
    public static function getCookie($cookie)
    {
        $parentCookie = self::getCookieParent($cookie);
        $cookieData = json_decode(
                                isset(self::$cookies[$parentCookie])?
                                self::$cookies[$parentCookie]:'{}'
                        , true);

        if(isset($cookieData[$cookie]))
            return $cookieData[$cookie];
        else
            return null;

    }

    /**
     * Check if there is needed cookie set or not
     * @param  string  $cookie Name of cookie
     * @return boolean         true if cookie is present else false
     */
    public static function hasCookie($cookie)
    {
        if(null !== self::getCookie($cookie))
            return true;
        else
            return false;
    }

    /**
     * Clear all PMA cookies 
     * 
     * @return true 
     *
     */

    public static function clearAllCookies()
    {
        self::$cookies = [];
        foreach($_COOKIE as $cookie => $value)
            setCookie( 
                $cookie,
                '',
                time() - 3600,
                $GLOBALS['PMA_Config']->getCookiePath(),
                '',
                $GLOBALS['PMA_Config']->isHttps()
                );
        
        $_COOKIE = array();

        return true;
    }
    /**
     * Gives parent index of a cookie
     * @param  string $cookie Name of cookie
     * @return string         Name of parent index
     */
    public static function getCookieParent($cookie)
    {

        if(strpos($cookie, 'pmaUser') !== false)
            return 'pmaUser-N';
        elseif(strpos($cookie,'pma-iv')  !== false 
                || strpos($cookie, 'pamlang') !== false)
            return 'pmaAuth-N';
        else
            return 'pmaConfig';
    }


}