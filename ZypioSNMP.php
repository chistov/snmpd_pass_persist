<?php
 /*Package originally developed by Mike Mackintosh
 * Fixed all bugs with pass_persist and performance by Sergey Chistov < msn_mailbox@mail.ru >
 * Requires >= PHP5.4
 *
 ****************************************************/

class ZypioSNMP{

	private $oid,
    $tree = [],
    $treeKeys = [];

  private $sensorsLastReadTime = 0;

	const INTEGER = "integer";
	const STRING = "string";
	const IPADDR = "ipaddress";
	const NETADDR = "NetworkAddress";
	const GAUGE = "gauge";
	const COUNTER = "counter64";
	const TIME = "timeticks";
	const OBJ = "objectid";
	const OPAQUE = "opaque";


	/**
	 * Create Class with Base OID
	 * 
	 * @param [type] $oid [description]
	 */
	public function __construct( $oid ){

		if(strpos($oid, ".") !== 0){
			$oid = ".{$oid}";
		}

		// Store base OID locally
		$this->oid = $oid;

	}

	/**
	 * Add an OID to your base OID
	 * 
	 * @param string $oid   The OID you wish to store data for
	 * @param string $type  Your OID type, STRING,Integer, etc
	 * @param multiple $value The value of your OID
	 */
	public function addOid( $oid, $type = STRING, $value= NULL, $allowed = [] ){

		$this->tree[$oid] = [ 'type' => $type, 'value' => $value, 'allowed' => $allowed ];

		return $this;
	}

  public function initTree(){
		$this->treeKeys = array_keys($this->tree);
		natsort($this->treeKeys);
		$this->tree = array_merge($this->treeKeys, $this->tree);
    // DEBUG( "full tree :". DEBUG_VAR($this->tree));
  }

	public function getNextOid( $requested_oid ){

		// Sort Response
		$local_oids = $this->treeKeys;
		$last = array_shift($local_oids);
		array_unshift($local_oids, $last);

		// Loop Through now
		for($i=0;$i<sizeof($local_oids);$i++){

			/*
			echo "Looking for OID: $local_oids[$i]\n";
			//*/

			// If no sub-oid was provided, return the first
			if( $requested_oid == $this->oid ){
				
				echo "{$this->oid}{$last}".PHP_EOL;
				echo $this->tree[ $last ]['type'] .PHP_EOL;
				echo $this->tree[ $last ]['value'] .PHP_EOL;
				return; //exit(0);

			}
			else if( version_compare( $requested_oid, $this->oid . $local_oids[$i], "<")) {
			
				echo "{$this->oid}{$local_oids[$i]}".PHP_EOL;
				echo $this->tree[ $local_oids[$i] ]['type'] .PHP_EOL;
				echo $this->tree[ $local_oids[$i] ]['value'] .PHP_EOL;
				return; //exit(0);

			}

		}

		// Per RFC, if there is nothing left, respond with NONE
    // DEBUG("NONE");
		echo $this->oid . PHP_EOL;
		echo "NONE\n".PHP_EOL;
		return; //exit(0);

	}

	public function getOid( $requested_oid ){

		// Get remainder
		preg_match("`{$this->oid}(.*)`", $requested_oid , $matches);
		
		// Set relative OID
		$oid = $matches[1];

		// Check if it exists
		if( array_key_exists( $oid, $this->tree )){
	
				echo "{$requested_oid}".PHP_EOL;
				echo $this->tree[ $oid ]['type'] .PHP_EOL;
				echo $this->tree[ $oid ]['value'] .PHP_EOL;
				return; //exit(0);

		}

		// Per RFC, if there is nothing left, respond with NONE
		echo "NONE".PHP_EOL;
		return; //exit(0);

	}


  private function getVals($line, $cntrName){
    $str = substr($line, strlen("$cntrName: "), -2/*chop last delimiter*/);
    return explode(';', $str);
  }

  private function getCounters($line, $cntrName){
    $vals = $this->getVals($line, $cntrName);
    $oid = "";
    switch($cntrName){
      case "link": $oid = ".2."; break;
      case "rx_packets": $oid = ".3."; break;
      case "rx_bytes": $oid = ".4."; break;
      case "tx_packets": $oid = ".5."; break;
      case "tx_bytes": $oid = ".6."; break;
      case "rx_crc_fail": $oid = ".7."; break;
      case "tx_crc_fail": $oid = ".8."; break;
    }
    for ($i = 0; $i < 64; ++$i)
      $this->tree[ $oid.$i ]['value'] = isset($vals[$i]) ? $vals[$i] : 0;
  }

  private function readSensors(){
    $nsFile = new _File("/opt/NS2/NS_STAT/ns_counters.log");
    if ($nsFile === null)
      DEBUG("failed to open file");
    while ($line = fgets($nsFile->getHandle())){
      if (strpos($line, 'link') !== false)
        $this->getCounters($line, 'link');
      elseif (strpos($line, 'rx_packets') !== false)
        $this->getCounters($line, 'rx_packets');
      elseif (strpos($line, 'rx_bytes') !== false)
        $this->getCounters($line, 'rx_bytes');
      elseif (strpos($line, 'tx_packets') !== false)
        $this->getCounters($line, 'tx_packets');
      elseif (strpos($line,'tx_bytes') !== false)
        $this->getCounters($line, 'tx_bytes');
      elseif (strpos($line, 'rx_crc_fail') !== false)
        $this->getCounters($line, 'rx_crc_fail');
      elseif (strpos($line, 'tx_crc_fail') !== false)
        $this->getCounters($line, 'tx_crc_fail');
      else
        DEBUG("unknown counter");
    }
  }

	public function respond(){
    $this->readSensors();

		// This checks for a GET/GETNEXT or SET
		// NOTE: Only GET/GETNEXT is support ATM
		if( array_key_exists(1, $_SERVER['argv'])) {
      file_put_contents("/tmp/ex.log", "ordinary\n", FILE_APPEND);

			// Look for getnext
			if( $_SERVER['argv'][1] == "-n" )
				$this->getNextOid( $_SERVER['argv'][2] );
			else if( $_SERVER['argv'][1] == "-g" )
				$this->getOid( $_SERVER['argv'][2] );
		
		}
		// PASS_PERSIST 
		else{
			
			while(true){
				$stdin = trim(fread( STDIN, 1024));

        if (time() - $this->sensorsLastReadTime > 5){
          $this->readSensors();
          $this->sensorsLastReadTime = time();
        }
				// If PING is received, respond with PONG
				if( stristr($stdin, "PING") ) {

					echo "PONG\n";

				}

				// If getnext is received, respond with getnext oid
				else if( stristr($stdin, "getnext") )
					$this->getNextOid(explode("\n", $stdin)[1]);

				// If get is received, respond with oid
				else if( stristr($stdin, "get") )
					$this->getOid(explode("\n", $stdin)[1]);
			}
		}
	}
}
