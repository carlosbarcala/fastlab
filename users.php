<?php

class Users extends Api {

    public function list($data ) {
        // comprobar si es sudoer
        if ( !isset($_SESSION['sudoer']) || $_SESSION['sudoer'] != true ) {
            return [
                'users' => [] ,
            ];
        }

        $response = [
            'users' => [] ,
        ];


        $users = shell_exec('cat /etc/passwd | grep /home');
        $users = explode("\n", trim($users ));
        foreach ($users as $index => $user) {
            $user = explode(':', $user);
            $response['users'][] = [
                    'name' => trim($user[0]),
                    'home' => trim($user[5]),
                    'shell' => trim($user[6])
                ];
            // detectar si es sudoer
            $sudoer = self::sendCommand('sudo -l -U ' . $user[0]);
            if (strpos($sudoer, 'not allowed to run sudo') === false) {
                $response['users'][ $index ]['sudoer'] = true;
            } else {
                $response['users'][ $index ]['sudoer'] = false;
            }
        }

        return [ 'data' => $response ];
    }

    public function create($data ) {
        // comprobar si es sudoer
        if ( !isset($_SESSION['sudoer']) || $_SESSION['sudoer'] != true ) {
            return [
                'status' => 'ko' ,
            ];
        }

        $username = $data["username"];
        $password = $data['password'];
        $sudoer = $data['sudoer'];

        $response = [
            'status' => 'ok' ,
            'data' => [
                'valid' => true
            ]
        ];

        //limpiar variables
        $username = escapeshellcmd($username);
        $password = $password;
        // crear usuario y contraseña
        $command = "useradd -m -s /bin/bash $username";
        $output = self::sendCommand($command);
        $command = "echo '$username:$password' | chpasswd";
        $output = self::sendCommand($command);

        if ($sudoer) {
            // añadir como sudoer
            $command = "usermod -aG sudo $username";
            $output = self::sendCommand($command);
        }

        // crear el usuario en samba
        $command = "echo '$password' | smbpasswd -s -a $username";
        $output = self::sendCommand($command);
        // crear el directorio /public si no existe
        $command = "mkdir -p /home/$username/public";
        $output = self::sendCommand($command);
        // cambiar el propietario del directorio /public
        $command = "chown $username:$username /home/$username/public";
        $output = self::sendCommand($command);
        // generar contraseña aleatorio facil de recordar 
        // crear una base de datos para el usuario para mariadb
        $command = "mariadb -u root -e \"CREATE DATABASE $username; GRANT ALL PRIVILEGES ON $username.* TO '$username'@'localhost' IDENTIFIED BY '$password'; FLUSH PRIVILEGES;\"";
        $output = self::sendCommand($command);
        
        return $response;
    }

    public function delete($data) {
        // comprobar si es sudoer
        if ( !isset($_SESSION['sudoer']) || $_SESSION['sudoer'] != true ) {
            return [
                'status' => 'ko' ,
            ];
        }

        $username = $data["username"];

        $response = [
            'status' => 'ok' ,
            'data' => [
                'valid' => true
            ]
        ];

        //limpiar variables
        $username = escapeshellcmd($username);
        // borrar usuario
        $command = "userdel -r $username";
        $output = self::sendCommand($command);
        // borrar el usuario en samba
        $command = "smbpasswd -x $username";
        $output = self::sendCommand($command);
        // eliminar el directorio /public si existe
        $command = "rm -rf /home/$username/public";
        $output = self::sendCommand($command);
        // eliminar la base de datos de mariadb
        $command = "mariadb -u root -e \"DROP DATABASE $username;\"";
        $output = self::sendCommand($command);

        return $response;
    }
  
  }