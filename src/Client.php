<?php
namespace Chez14\Desso;

class Client {
    private static
        $base_url = "https://sso.unpar.ac.id/",
        $cas_login = "login",
        $cas_logout = "logout",
        $user_agent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36";

    const
        EXPATTERN = '/<input type="hidden" name="execution" value="([\w\-\_\/\+\=]+)"/',
        ERRPATTERN = '/(The credentials you provided cannot be determined to be authentic|Invalid credentials)\./i',
        SUCPATTERN = '/have successfully logged into the/i';
    
    protected
        $guzzleSetting,
        $u_username,
        $u_password,
        $guzzleClient,
        $guzzleHandlerStack,
        $cookieJar;
    
    public function __construct($params = []) {
        $this->guzzleHandlerStack = \GuzzleHttp\HandlerStack::create();
        if(key_exists("cookie", $params)) {
            $this->cookieJarUse($params['cookie'], false);
        } else {
            $this->resetCookie(false);
        }

        $this->guzzleSetting = [
            'base_uri' => self::$base_url,
            'allow_redirects' => false,
            'headers' => [
                'User-Agent' => self::$user_agent
            ],
            'handler' => $this->guzzleHandlerStack,
            'cookies' => $this->cookieJar,
        ];
        if(key_exists("guzzle", $params)) {
            $this->guzzleSetting = array_merge($this->guzzleSetting, $params);
        }
        
        $this->refreshGuzzle();
    }

    /**
     * Refresh the Guzzle instance, just in case if you made a changes in cookie
     * or settings.
     */
    protected function refreshGuzzle() {
        $this->guzzleClient = new \GuzzleHttp\Client($this->guzzleSetting);
    }

    /**
     * Sets the credential for logining the SSO.
     */
    public function setCredential($username, $password) {
        $this->u_username = $username;
        $this->u_password = $password;
    }

    public function login($username = null, $password = null):bool {
        if(empty($username) || empty($password)) {
            $username = $this->u_username;
            $password = $this->u_password;
            
            if(empty($username) || empty($password)) {
                throw new Exceptions\CredentialException("Password/Username is empty!");
            }
        }

        $client = $this->guzzleClient;
        // make session, save it to query
        $resp = $client->request('GET', self::$cas_login);
        $ex_match = [];
        preg_match_all(self::EXPATTERN, $resp->getBody(), $ex_match);

        try {
            // build query, then fetch it
            $resp = $client->request('POST', self::$cas_login, [
                'form_params'=> [
                    'username'  => $username,
                    'password'  => $password,
                    'execution' => $ex_match[1][0],
                    '_eventId'  => 'submit',
                    'submit'    => 'LOGIN'
                ]
            ]);
        } catch (GuzzleHttp\Exception\ClientException $e) {
            throw new Exceptions\CredentialException('Password Salah');
        }
        //error checking
        if(preg_match(self::ERRPATTERN, $resp->getBody()))
            throw new Exceptions\CredentialException('Password Salah');

        return true;
    }

    public function serviceLogin(ServiceBase $service) {

    }

    public function loginValidate():bool {
        $client = $this->guzzleClient;
        // make session, save it to query
        $resp = $client->request('GET', self::$cas_login);
        $match = [];
        preg_match_all(self::SUCPATTERN, $resp->getBody(), $match);

        if(count($match[0])> 0)
            return true;
        return false;
    }

    public function cookieJarUse($cookiejar, $resetGuzzle=true) {
        $pastCookie = [];
        
        $this->cookieJar = new \GuzzleHttp\Cookie\FileCookieJar($cookiejar, true);
        
        if($resetGuzzle) {
            $this->refreshGuzzle();
        }
    }

    public function cookieJarSave($saveTo) {
        $this->cookieJar->save($saveTo);
    }

    /**
     * Gunakan ini untuk membersihkan cookie yang barusan anda load.
     * Method ini akan membuat CookieJar baru. Dan yang lama tidak akan
     * terpengaruh.
     *
     * @param $hardReset Set true untuk menyimpan cookie yang lama.
     */
    public function resetCookie($resetGuzzle = true){
        if(!is_dir(__DIR__ . "/../tmp")) {
            \mkdir(__DIR__ . "/../tmp");
            
        }
        $tmpfname = tempnam(__DIR__ . "/../tmp", "cookie");
        $this->cookieJar = new \GuzzleHttp\Cookie\FileCookieJar($tmpfname, true);

        if($resetGuzzle) {
            $this->refreshGuzzle();
        }
    }
}