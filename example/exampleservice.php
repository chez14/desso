<?php
use \Chez14\Desso\ServiceBase;
use \Chez14\Desso\Client;

class StudentPortal extends ServiceBase {
    const
        BASE_URL="https://oldstudentportal.unpar.ac.id/",
        IGNITE_URL="home/index.login.submit.php";
    
    const
        GUZZLE_SETTING=[
            'base_uri' => self::BASE_URL,
            'cookies' => true,
            'allow_redirects' => [
                'max'             => 5,
                'strict'          => false,
                'referer'         => true,
                'protocols'       => ['https'],
                'track_redirects' => false
            ],
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36'
            ],
            'verify' => false
        ];
    
    protected
        $client=null;

    public function pre_login(){
        if($this->client == null)
            $this->client = new \GuzzleHttp\Client(self::GUZZLE_SETTING);
        return $this->client->request('POST', self::IGNITE_URL, [
            'form_data'>[
                'Submit'=>'Login'
            ]
        ]);
    }

    public function post_login(String $ticket) {
        $resp = $this->client->request('GET',self::IGNITE_URL, [
            'query'=>[
                'ticket'=>$ticket
            ]
        ]);
        return true;
    }

    public function get_client(){
        return $this->client;
    }

    public function get_service():String {
        return self::BASE_URL . self::IGNITE_URL;
    }
}