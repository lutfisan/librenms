<?php

// Check if the index is available
$index = array_key_first($port_stats);
if (!isset($port_stats[$index]['ifType'])) {
    $entries = [];
    $entries = snmpwalk_cache_oid($device, 'ipeCfgPortModemEnable', $entries, 'IPE-SYSTEM-MIB');
    $entries = snmpwalk_cache_oid($device, 'ipeStsPortEtherLinkUp', $entries, 'IPE-SYSTEM-MIB');
    $entries = snmpwalk_cache_oid($device, 'invMacAddress', $entries, 'IPE-COMMON-MIB');
    $entries = snmpwalk_cache_oid($device, 'atpcPowerMode', $entries, 'IPE-COMMON-MIB');
    $entries = snmpwalk_cache_oid($device, 'asETHPortInterfaceType', $entries, 'IPE-COMMON-MIB');
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
    }
}