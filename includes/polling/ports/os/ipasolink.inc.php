// File: /opt/librenms/includes/polling/ports/ipasolink.inc.php
<?php

if (!$has_ifEntry && $has_ifXEntry) {
    $entries = [];
    $entries = snmpwalk_cache_oid($device, 'ipeCfgPortEtherEnable', $entries, 'IPE-SYSTEM-MIB');
    $entries = snmpwalk_cache_oid($device, 'ipeCfgPortModemEnable', $entries, 'IPE-SYSTEM-MIB');
    $entries = snmpwalk_cache_oid($device, 'ipeStsPortEtherLinkUp', $entries, 'IPE-SYSTEM-MIB');
    $entries = snmpwalk_cache_oid($device, 'ipeStsPortEtherDuplex', $entries, 'IPE-SYSTEM-MIB');
    $entries = snmpwalk_cache_oid($device, 'invMacAddress', $entries, 'IPE-COMMON-MIB');
    $entries = snmpwalk_cache_oid($device, 'atpcPowerMode', $entries, 'IPE-COMMON-MIB');
    $entries = snmpwalk_cache_oid($device, 'modemPsOff', $entries, 'IPE-COMMON-MIB');
    $entries = snmpwalk_cache_oid($device, 'asETHPortInterfaceType', $entries, 'IPE-COMMON-MIB');
    $entries = snmpwalk_cache_oid($device, 'asETHPortSpeedDuplex', $entries, 'IPE-COMMON-MIB');
    $entries = snmpwalk_cache_oid($device, 'asETHPortAdminStatus', $entries, 'IPE-COMMON-MIB');
    $entries = snmpwalk_cache_oid($device, 'asETHPortOperStatus', $entries, 'IPE-COMMON-MIB');
    d_echo($entries);

    foreach ($port_stats as $ifIndex => $port) {
        $entry = isset($entries[$ifIndex]) ? $entries[$ifIndex] : [];

        // Determine ifType
        if (isset($entry['ipeStsPortEtherLinkUp'], $entry['invMacAddress']) ||
            str_starts_with($port['ifName'], 'eth') || str_starts_with($port['ifName'], 'bcm')) {
            $port_stats[$ifIndex]['ifType'] = 'ethernetCsmacd';
        } elseif (isset($entry['asETHPortInterfaceType'])) {
            switch ($entry['ipeStsPortEtherDuplex']) {
                case 'fiber':
                    $port_stats[$ifIndex]['ifType'] = 'opticalChannel';
                    break;
                case 'copper':
                    $port_stats[$ifIndex]['ifType'] = 'ethernetCsmacd';
                    break;
                default:
                    $port_stats[$ifIndex]['ifType'] = 'other';
            }
        } elseif (str_starts_with($port['ifName'], 'lo')) {
            $port_stats[$ifIndex]['ifType'] = 'softwareLoopback';
        } elseif (isset($entry['atpcPowerMode'])) {
            $port_stats[$ifIndex]['ifType'] = 'otnOdu';
        } elseif (!str_starts_with($port['ifName'], 'lldp')) {
            $port_stats[$ifIndex]['ifType'] = 'other';
        } else {
            continue;
        }

        // Determine ifAdminStatus
        if (isset($entry['ipeCfgPortEtherEnable'])) {
            switch ($entry['ipeCfgPortEtherEnable']) {
                case 'enabled':
                case '1':
                    $port_stats[$ifIndex]['ifAdminStatus'] = 'up';
                    break;
                case 'disabled':
                case '2':
                    $port_stats[$ifIndex]['ifAdminStatus'] = 'down';
                    break;
                default:
                    $port_stats[$ifIndex]['ifAdminStatus'] = 'testing';
            }
        } elseif (isset($entry['asETHPortAdminStatus'])) {
            switch ($entry['asETHPortAdminStatus']) {
                case 'normal':
                case 'oamSend':
                    $port_stats[$ifIndex]['ifAdminStatus'] = 'up';
                    break;
                case 'force':
                    $port_stats[$ifIndex]['ifAdminStatus'] = 'down';
                    break;
                default:
                    $port_stats[$ifIndex]['ifAdminStatus'] = 'testing';
            }
        } elseif (isset($entry['ipeCfgPortModemEnable'])) {
            $port_stats[$ifIndex]['ifAdminStatus'] = $entry['ipeCfgPortModemEnable'] === 'enabled' ? 'up' : 'down';
        } elseif (str_starts_with($port['ifName'], 'lo')) {
            $port_stats[$ifIndex]['ifAdminStatus'] = 'up';
        } elseif (isset($entry['modemPsOff'])) {
            $port_stats[$ifIndex]['ifAdminStatus'] = $entry['modemPsOff'] === 'on' ? 'up' : 'down';
        } else {
            $port_stats[$ifIndex]['ifAdminStatus'] = 'up';
        }

        // Determine ifOperStatus
        if (isset($entry['ipeStsPortEtherLinkUp'])) {
            switch ($entry['ipeStsPortEtherLinkUp']) {
                case '1':
                    $port_stats[$ifIndex]['ifOperStatus'] = 'up';
                    break;
                case '2':
                    $port_stats[$ifIndex]['ifOperStatus'] = 'down';
                    break;
                default:
                    $port_stats[$ifIndex]['ifOperStatus'] = 'unknown';
            }
        } elseif (isset($entry['asETHPortOperStatus'])) {
            switch ($entry['asETHPortOperStatus']) {
                case 'linkDown':
                case '1':
                    $port_stats[$ifIndex]['ifOperStatus'] = 'down';
                    break;
                case 'linkUp':
                case '2':
                    $port_stats[$ifIndex]['ifOperStatus'] = 'up';
                    break;
                default:
                    $port_stats[$ifIndex]['ifOperStatus'] = 'unknown';
            }
        } elseif (isset($entry['ipeCfgPortModemEnable'])) {
            $port_stats[$ifIndex]['ifOperStatus'] = $entry['ipeCfgPortModemEnable'] === 'enabled' ? 'up' : 'down';
        } elseif (str_starts_with($port['ifName'], 'lo')) {
            $port_stats[$ifIndex]['ifOperStatus'] = 'up';
        } elseif (isset($entry['atpcPowerMode'])) {
            $port_stats[$ifIndex]['ifOperStatus'] = $entry['atpcPowerMode'] === 'active' ? 'up' : 'down';
        } else {
            $port_stats[$ifIndex]['ifOperStatus'] = 'unknown';
        }

        // Determine ifDuplex
        if (isset($entry['ipeStsPortEtherDuplex'])) {
            switch ($entry['ipeStsPortEtherDuplex']) {
                case '1':
                    $port_stats[$ifIndex]['ifDuplex'] = 'halfDuplex';
                    break;
                case '2':
                    $port_stats[$ifIndex]['ifDuplex'] = 'fullDuplex';
                    break;
                default:
                    $port_stats[$ifIndex]['ifDuplex'] = 'unknown';
            }
        } elseif (isset($entry['asETHPortSpeedDuplex'])) {
            switch ($entry['asETHPortSpeedDuplex']) {
                case 's10M-HALF':
                case 's100M-HALF':
                    $port_stats[$ifIndex]['ifDuplex'] = 'halfDuplex';
                    break;
                case 's10M-FULL':
                case 's100M-FULL':
                case 's1000M-FULL':
                    $port_stats[$ifIndex]['ifDuplex'] = 'fullDuplex';
                    break;
                default:
                    $port_stats[$ifIndex]['ifDuplex'] = 'unknown';
            }
        }

        // Physical address
        if (isset($entry['invMacAddress'])) {
            $port_stats[$ifIndex]['ifPhysAddress'] = $entry['invMacAddress'];
        }
    }
}