#!/usr/bin/php
<?php
/*****************************************************
 * Package ZypioSNMP
 * Description ZypioSNMP is used to datafill SNMP OID's
 *             using SNMP's pass functionality
 * Author Mike Mackintosh < m@zyp.io >
 * Date 20130703
 * Version 1.1
 *
 * Requires >= PHP5.4
 *
 ****************************************************/

require_once( __DIR__ . "/ZypioSNMP.php");
require_once( __DIR__ . "/IO.php");



// DEBUG("stdin: ". DEBUG_VAR($_SERVER));
// Log Request
//file_put_contents("/tmp/zypiosnmp.log", "Conneciton Received\n", FILE_APPEND);


// Instantiate Class using Base OID
// -- this base OID must match what you  
// added the pass statement in snmpd.conf
$snmp = new ZypioSNMP(".1.3.6.1.4.1.42779.3");

// The below syntax adds to the base .1.3.6.1.4.1.38741
///*
//

// DEBUG("counters: ". DEBUG_VAR($counters));

$snmp->addOid(".1", ZypioSNMP::STRING, "NanoSwitch 3"); // .1.3.6.1.4.1.38741.1.0

for ($i = 0; $i < 64; ++$i){
  $snmp->addOid(".2." . $i, ZypioSNMP::COUNTER, 0);//$counters['link'][$i]);
  $snmp->addOid(".3." . $i, ZypioSNMP::COUNTER, 0);//$counters['rx_packets'][$i]);
  $snmp->addOid(".4." . $i, ZypioSNMP::COUNTER, 0);//$counters['rx_bytes'][$i]);
  $snmp->addOid(".5." . $i, ZypioSNMP::COUNTER, 0);//$counters['tx_packets'][$i]);
  $snmp->addOid(".6." . $i, ZypioSNMP::COUNTER, 0);//$counters['tx_bytes'][$i]);
  $snmp->addOid(".7." . $i, ZypioSNMP::COUNTER, 0);//$counters['rx_crc_fail'][$i]);
  $snmp->addOid(".8." . $i, ZypioSNMP::COUNTER, 0);//$counters['tx_crc_fail'][$i]);
}

$snmp->initTree();
$snmp->respond();

