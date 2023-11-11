<?php

class System extends Api {


    public function modules() {

        $modules_version = [
            'php' => 'php -v | head -n 1 | cut -d " " -f 2',
            'apache' => "apache2 -v | grep -oP 'Apache/\K[0-9.]+'",
            'mariadb' => "mariadb -V | awk '{print $5}'",
            'node' => 'node -v',
            'npm' => 'npm -v',
            'python3' => "python3 -c \"import sys; print(sys.version.split(' ')[0])\"",
            'pip' => "pip --version | awk '{print $2}'",
            'git' => "git --version | awk '{print $3}'",
            'composer' => "composer --version | grep -oP 'Composer version \K[0-9]+\.[0-9]+\.[0-9]+'",
            'docker' => "docker --version | awk '{print $3}'",
        ];

        // obtener las versiones
        $modules = [];
        foreach ($modules_version as $module => $command) {
            $modules[$module] = self::sendCommand($command);
            // quitar espacios y saltos de linea
            $modules[$module] = trim($modules[$module]);
        }

        return [ 'data' => $modules ];
        
    }

    public function hardware() {



        $hardware = [
            'cpu' => 'cat /proc/cpuinfo | grep "model name" | head -n 1 | cut -d ":" -f 2',
            'ram' => 'free -h | grep Mem | awk \'{print $2}\'',
            // nombre de la GPU y memoria
            'gpu' => 'lspci | grep VGA | cut -d ":" -f 3',
        ];



        // obtener las versiones
        $hardware_info = [];
        foreach ($hardware as $module => $command) {
            $hardware_info[$module] = self::sendCommand($command);
            // quitar espacios y saltos de linea
            $hardware_info[$module] = trim($hardware_info[$module]);
        }

        // obtener los discos y el tamaño de cada uno
        $discs = [];
        $discs_info = self::sendCommand('lsblk -o NAME,SIZE -d -n -l');
        $discs_info = trim($discs_info);
        $discs_info = explode("\n", $discs_info);
        foreach ($discs_info as $disc_info) {
            $disc_info = trim($disc_info);
            $explode_info = preg_split('/\s+/', $disc_info);
            $name = trim($explode_info[0]);
            $size = trim($explode_info[1]);
            $hardware_info[$name] = $size;
        }

        return [ 'data' => $hardware_info ];
        
    }

  
}