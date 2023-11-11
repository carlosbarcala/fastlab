<?php

class Monitor extends Api {


    public function system($data) {

        $response = [
            'memory' => [] ,
            'cpu' => [] ,
            'gpu' => [] ,
            'disk' => [] 
        ];

        // MEM
        $total = shell_exec('grep MemTotal /proc/meminfo');
        preg_match('/MemTotal:\s+(\d+)\s+kB/', $total, $matches);
        $total = (float) $matches[1];
        $free = shell_exec('grep MemAvailable /proc/meminfo'); 
        preg_match('/MemAvailable:\s*(\d+)\s*kB/', $free, $matches);
        $free = (float) $matches[1];
        $used = $total - $free;

        $response['memory'] = [ 'total' => $total*1024, 'free' => $free*1024 ];

        // SWAP Memory
        $total = shell_exec('grep SwapTotal /proc/meminfo');
        preg_match('/SwapTotal:\s+(\d+)\s+kB/', $total, $matches);
        $total = (float) $matches[1];
        $free = shell_exec('grep SwapFree /proc/meminfo');
        preg_match('/SwapFree:\s*(\d+)\s*kB/', $free, $matches);
        $free = (float) $matches[1];
        $used = $total - $free;

        $response['swap'] = [ 'total' => $total*1024, 'free' => $free*1024 ];

        // CPU
        $stat1 = $this->GetCoreInformation();
        sleep(1);
        $stat2 = $this->GetCoreInformation();
        $data = $this->GetCpuPercentages( $stat1, $stat2 );
        $response['cpu'] = $data;

        //obtener todas las temperaturas del sistema
        $temperatures = shell_exec('sensors -u | grep temp');
        $list = explode( "\n", trim($temperatures) );
        $response['temperatures'] = $list;
        


        //GPU

        //MEM
        //nvidia-smi --query-gpu=utilization.gpu,memory.total,memory.free --format=csv,noheader,nounits
        $mem = shell_exec('nvidia-smi --query-gpu=utilization.gpu,memory.total,memory.free --format=csv,noheader,nounits');
        $list = explode( "\n", trim($mem) );

        $response['gpu'] = [];

        for($i=0; $i<count($list); $i++) {
            $values = explode( ", ", trim($list[$i]) );
            $total = ( (float) $values[1] ) * 1024 * 1024;
            $free = ( (float) $values[2] ) * 1024 * 1024;
            $used = $total - $free;
            $use = $values[0];
            if ( $use == '[N/A]' ) $use = 0;
            $response['gpu'][$i] = [ 'memory' => [
                                          'total' => $total ,
                                          'free' => $free ,
                                        ] ,
                                    'usage' => $use
                                    ];
        }

        return [ 'data' => $response ];
    }
  
    function GetCoreInformation() {
        $data = file('/proc/stat');
        $cores = array();
        foreach( $data as $line ) {
            if( preg_match('/^cpu[0-9]/', $line) )
            {
                $info = explode(' ', $line );
                $cores[] = array(
                    'user' => $info[1],
                    'nice' => $info[2],
                    'sys' => $info[3],
                    'idle' => $info[4]
                );
            }
        }
        return $cores;
    }
    
    function GetCpuPercentages($stat1, $stat2) {
      if( count($stat1) !== count($stat2) ) {
        return;
      }
      $cpus = array();
      for( $i = 0, $l = count($stat1); $i < $l; $i++) {
        $dif = array();
        $dif['user'] = $stat2[$i]['user'] - $stat1[$i]['user'];
        $dif['nice'] = $stat2[$i]['nice'] - $stat1[$i]['nice'];
        $dif['sys'] = $stat2[$i]['sys'] - $stat1[$i]['sys'];
        $dif['idle'] = $stat2[$i]['idle'] - $stat1[$i]['idle'];
        $total = array_sum($dif);
        $cpu = array();
        foreach($dif as $x=>$y) $cpu[$x] = round($y / $total * 100, 1);
        $cpus['cpu' . $i] = $cpu;
      }
      return $cpus;
    }

    public function temperatures($data) {

      // Ejecutar sensors y obtener salida  
      $output = shell_exec('sensors -j'); 

      $data = json_decode($output, true);

      // obtener los de las gpus
      $temp = shell_exec('nvidia-smi --query-gpu=temperature.gpu --format=csv,noheader,nounits');
      $list = explode( "\n", trim($temp) );
      for ($i=0; $i<count($list); $i++) {
        $data['nvidia']['Adapter'] = 'PCI adapter';
        $data['nvidia']['GPU '.$i]['temp'.($i+1).'_input'] = $list[$i];
      }

      return [ 'data' => $data ];

    }

    // obtener los consumos
    public function consumption($data) {

      require('tuya.php');

      $tuya = new TuyaCloud([
        "userName" => "carlosthec@gmail.com", // username/email to access to SmartLife/Tuya app
        "password" => "reina291I$", // password to access to SmartLife/Tuya app
        "bizType" => "smart_life", // type ('tuya' or 'smart_life')
        "countryCode" => "34", // Country code (International dialing number), e.g. "33" for France or "1" for USA
        "region" => "eu" // region (az=Americas, ay=Asia, eu=Europe)
      ]);

      $smartlife_devices = [];

      // to get a list of your devices
      $devices = $tuya->getDevices();
      var_dump( $devices );
      foreach($devices as $device) {
        $smartlife_devices[$device->id] = $device;
        // obtener el consumo
        $status = $tuya->getState([
          "id" => $device->id,
          'name' => 'Deshumificador'
        ]);
        var_dump( $status );
      }




    }



  }

