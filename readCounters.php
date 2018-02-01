#!/usr/bin/php
<?php
 /*Package originally developed by Mike Mackintosh
 * Fixed all bugs with pass_persist and performance by Sergey Chistov < msn_mailbox@mail.ru >
 * Requires >= PHP5.4
 *
 ****************************************************/
require_once( __DIR__ . "/IO.php");
require_once( __DIR__ . "/ZypioSNMP.php");

// Instantiate Class using Base OID
// -- this base OID must match what you  
// added the pass statement in snmpd.conf
$snmp = new ZypioSNMP(".1.3.6.1.4.1.YourNumber");

// The below syntax adds to the base .1.3.6.1.4.1.38741
///*
//

// DEBUG("counters: ". DEBUG_VAR($counters));

$snmp->addOid(".1", ZypioSNMP::STRING, "My HW Name"); // .1.3.6.1.4.1.38741.1.0

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

