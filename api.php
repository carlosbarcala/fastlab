<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

class Api {

    public $checkToken = true;
    static public $clientToken;

    static public function handleRequest() {
        $json = file_get_contents('php://input');
        $body = json_decode($json, true);
        $action = $body['action'];
        $namespace = $body['namespace'];
        $data = $body['data'];
        self::$clientToken = $body['token'];

        if ( $namespace == 'app' ) {

            // comprobar si el método estatico existe
            if ( !method_exists('Api', $action) ) {
                $response = [
                    'status' => 'ko',
                    'data' => 'action not found'
                ];
                $jsonResponse = json_encode($response);
                header('Content-Type: application/json');
                echo $jsonResponse;
                return;
            }

            // ejecutar el método
            $response = Api::$action($data);

        } else {

            // cargar el archivo segun el namespace
            $file = $namespace . '.php';
            if ( !file_exists($file) ) {
                $response = [
                    'status' => 'ko',
                    'data' => 'namespace not found'
                ];
                $jsonResponse = json_encode($response);
                header('Content-Type: application/json');
                echo $jsonResponse;
                return;
            }

            // cargar la clase
            require_once($file);

            // instanciar la clase
            $instance = new $namespace();

            if ( $instance->checkToken ) {
                if ( !array_key_exists('token', $_SESSION ) ) {
                    $response = [
                        'status' => 'ko',
                        'data' => 'not permitted'
                    ];
                    $jsonResponse = json_encode($response);
                    header('Content-Type: application/json');
                    echo $jsonResponse;
                    return;
                }
                if ( self::$clientToken != $_SESSION['token'] ) {
                    $response = [
                        'status' => 'ko',
                        'data' => 'not permitted'
                    ];
                    $jsonResponse = json_encode($response);
                    header('Content-Type: application/json');
                    echo $jsonResponse;
                    return;
                }
            }

            // comprobar si el método existe
            if ( !method_exists($instance, $action) ) {
                $response = [
                    'status' => 'ko',
                    'data' => 'action not found'
                ];
                $jsonResponse = json_encode($response);
                header('Content-Type: application/json');
                echo $jsonResponse;
                return;
            }
            // ejecutar el método
            $response = $instance->$action( $data );

        } 

        // crear respuesta
        $jsonResponse = json_encode($response);

        // establecer cabecera de tipo de contenido a JSON
        header('Content-Type: application/json');

        // imprimir respuesta JSON
        echo $jsonResponse;

    }

    static public function auth($data) {

        $username = $data["username"];
        $password = $data["password"];
        $response = [
            'data' => ['valid' => 'ko'],
        ];
        //limpiar variables
        $username = escapeshellcmd($username);
        //$password = escapeshellcmd($password);
        $command = "echo '$password' | su -c 'echo success' $username";
        $output = shell_exec($command);
        if ( $output == null ) {
            $response['data']['valid'] = 'ko';
        }
        else if (  str_contains( $output, "success") ) {
            $token = bin2hex(random_bytes(16));
            //añadir la ip al token al inicio para evitar que se pueda usar desde otra ip
            // se encripta para que no se pueda ver la ip
            $token = md5($_SERVER['REMOTE_ADDR']) .'-'.$token;
            $_SESSION['token'] = $token;
            $response['token'] = $token;
            $response['data']['valid'] = 'ok';
            $response['data']['token'] = $token;
            $response['data']['username'] = $username;
            // detectar si es sudoer y lo guardamos en la sesión
            $sudoer = self::sendCommand('sudo -l -U ' . $username);
            if (strpos($sudoer, 'not allowed to run sudo') === false) {
                $_SESSION['sudoer'] = true;
            } else {
                $_SESSION['sudoer'] = false;
            }
            $response['token'] = $token;
        }
        return $response;
    }

    static public function check($data) {
        $data = [
            'valid' => false 
        ];
        $token = null;
        if ( array_key_exists('token', $_SESSION ) ) {
            if ( $_SESSION['token'] == self::$clientToken ) {
                $data['valid'] = true;
            }
        } 
        // comprobar si es sudoer
        if ( !isset($_SESSION['sudoer']) || $_SESSION['sudoer'] != true ) {
            $data['sudoer'] = false;
        } else {
            $data['sudoer'] = true;
        }

        return [ 'status' => 'ok' , 'data' => $data, 'token' => $token  ];
    }
  
    static public function sendCommand($command) {
        $data = http_build_query(array(
            'cmd' => $command
        ));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:32112/command.php');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    function post($uri, $body) {
        $ch = curl_init($uri);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'Content-Type: application/json'
        ));
        $result = curl_exec($ch);
        return json_decode($result);
      } 
  
}

Api::handleRequest();