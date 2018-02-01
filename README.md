# snmpd_pass_persist

HowTo:
Configure /etc/snmp/snmpd.conf:
 rocommunity public
 pass_persist .1.3.6.1.4.1.YourNumber /path/to/readCounters.php
 
#On remote host:

snmpwalk -v 2c -c public -O e YOURHOST_IP iso.3.6.1.4.1.YourNumber
