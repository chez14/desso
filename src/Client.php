<?php
namespace Chez14\Desso;

class Client {
    private static
        $base_url = "https://sso.unpar.ac.id/",
        $cas_login = "login",
        $cas_logout = "logout";
        
    public static
        $user_agent = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.96 Safari/537.36";

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
        $cookieJar,
        $cookieFile,
        $tempFolder,
        $useTempCookie = true;
    
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
        $client = $this->guzzleClient;

        $service->pre_login();
        $resp = $client->request("GET", self::$cas_login, [
            'query'=>[
                'service'=> $service->get_service()
            ]
        ]);

        parse_str(parse_url($resp->getHeader("Location")[0], PHP_URL_QUERY), $queries);
        return $service->post_login($queries['ticket']);
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
        $this->cookieFile = $cookieJar;
        $this->useTempCookie = false;

        $this->cookieJar = new \GuzzleHttp\Cookie\FileCookieJar($cookiejar, true);
        
        if($resetGuzzle) {
            $this->refreshGuzzle();
        }
    }

    public function cookieJarSave($saveTo = null) {
        if($saveTo == null){
            if($this->useTempCookie) {
                throw new \InvalidArgumentException("This time, \$saveTo is not allowed to be null.");
            }
            $saveTo = $this->cookieFile;
        }
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
        $tmpfname = tempnam($this->tempFolder, "cookie");
        $this->useTempCookie = true;
        $this->cookieFile = $tmpfname;
        $this->cookieJar = new \GuzzleHttp\Cookie\FileCookieJar($tmpfname, true);

        if($resetGuzzle) {
            $this->refreshGuzzle();
        }
    }

    public function __destruct() {
        if($this->useTempCookie)
            unlink($this->cookieFile);
    }
}