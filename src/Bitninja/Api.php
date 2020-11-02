<?php

namespace Bitninja;

class Api
{

    /**
     * @var string 
     */
    public $api_url = "https://reseller-api.bitninja.io/v1/";

    /**
     * @var string 
     */
    protected $api_key;

    /**
     * @var string 
     */
    protected $email;

    /**
     * @var string
     */
    protected $access_token;

    /**
     * @var resource
     */
    protected $connection;

    /**
     * BitninjaAPI constructor.
     * @param string $email
     * @param string $api_key
     */
    public function __construct($email, $api_key)
    {
        $this->email = $email;
        $this->api_key = $api_key;
    }

    /**
     * @throws Exception
     */
    public function validate(){
        if(empty($this->email) || empty($this->api_key)){
            throw new \Exception("Email or API Key is missing!");
        }
    }

    /**
     * @throws Exception
     */
    public function authenticate(){
        $this->validate();
        $curl = new GuzzleHttp\Client([
            'base_uri' => "https://reseller-api.bitninja.io/oauth/",
            'http_errors' => true,
            'verify' => false,
            'exceptions' => false
        ]);
        $response = $curl->post('access_token',['form_params' => [
            'grant_type' => 'client_credentials',
            'client_id' => $this->email,
            'client_secret' => $this->api_key
        ]]);
        if($response->getStatusCode() == 200){
            $output = json_decode($response->getBody());
            $this->access_token = $output->access_token;
            $this->connection = new GuzzleHttp\Client([
                'base_uri' => $this->api_url,
                'http_errors' => true,
                'verify' => false,
                'headers' => [
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Authorization' => "Bearer {$this->access_token}"
                ],
                'exceptions' => false
            ]);
        } else {
            throw new \Exception($response->getBody());
        }
    }

    /**
     * @param $endpoint
     * @param array $params
     * @param string $method
     * @return mixed
     * @throws Exception
     */
    public function call($endpoint, $params = [], $method = 'post'){
        if(empty($this->access_token)){
            throw new \Exception("Please authenticate first!");
        }
        $response = $this->connection->$method($endpoint, $params);
        $output = json_decode($response->getBody());
        if($output->code != 200){
            throw new \Exception($output->message.implode("\n",$output->fields));
        }
        return json_decode($response->getBody());
    }


    /**
     * API Methods Users
     */

    /**
     * @param $name
     * @param $email
     * @return mixed
     * @throws Exception
     */
    public function registerUser($name, $email){
        return $this->call('users',['form_params' => ['body' => json_encode(['name' => $name, 'email' => $email]) ] ]);
    }

    /**
     * @param $user_id
     * @return mixed
     * @throws Exception
     */
    public function getInstructions($user_id){
        $response = $this->call("users/{$user_id}/install",[],'get');
        return $response->message->instructions;

    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function listAllUsers(){
        $response = $this->call("users",[],'get');
        return $response->message;
    }

    /**
     * @param $user_id
     * @return mixed
     * @throws Exception
     */
    public function getUserById($user_id){
        $users = $this->listAllUsers();
        if(count($users) > 0){
            foreach ($users as $user){
                if($user->id == $user_id){
                    return $user;
                }
            }
        }
    }

    /**
     * @param $email
     * @return mixed
     * @throws Exception
     */
    public function getUserByEmail($email){
        $users = $this->listAllUsers();
        if(count($users) > 0){
            foreach ($users as $user){
                if($user->email == $email){
                    return $user;
                }
            }
        }
    }

    /**
     * @param $user_id
     * @return mixed
     * @throws Exception
     */
    public function detachUser($user_id){
        return $this->call("users/{$user_id}",['form_params' => ['user_id' => $user_id ]],'delete');
    }

    /**
     * API Methods Servers
     */

    /**
     * @return mixed
     * @throws Exception
     */
    public function listAllServers(){
        $response = $this->call("servers",[],'get');
        return $response->message;
    }

    /**
     * @param $user_id
     * @return mixed
     * @throws Exception
     */
    public function listUsersServers($user_id){
        $response = $this->call("users/{$user_id}/servers",[],'get');
        return $response->message;
    }


    /**
     * API Methods License Keys
     */

    /**
     * @param $user_id
     * @param $ip
     * @return mixed
     * @throws Exception
     */
    public function createLicenseKey($user_id, $ip){
        return $this->call("licensekeys",['form_params' => ['body' => json_encode(['user_id' => $user_id, 'ip_adddress' => $ip])] ],'post');
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function listAllLicenseKeys(){
        $response = $this->call("licensekeys",[],'get');
        return $response->message;
    }

    /**
     * @param $server_ip
     * @return mixed
     * @throws Exception
     */
    public function getLicenseKey($server_ip){
        $license_keys = $this->listAllLicenseKeys();
        foreach($license_keys as $key) {
            if($key->server_ip == $server_ip){
                return $key;
            }
        }
    }

    /**
     * @param $license_key
     * @return mixed
     * @throws Exception
     */
    public function terminateLicenseKey($license_key){
        return $this->call("licensekeys/{$license_key}",['form_params' => ['license_key' => $license_key]],'delete');
    }

    /**
     * API Methods Licenses
     */

    /**
     * @param $user_id
     * @param $ip
     * @return object
     * @throws Exception
     */
    public function attachLicense($user_id, $ip){
        return $this->call("licenses",['form_params' => ['body' => json_encode(['server_id' => $user_id])] ],'post');
    }

    /**
     * @param $server_id
     * @return object
     * @throws Exception
     */
    public function dettachLicense($server_id){
        return $this->call("licenses/{$server_id}",[],'get');
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function listAllLicense(){
        $response = $this->call("licenses",[],'get');
        return $response->message;
    }

}
