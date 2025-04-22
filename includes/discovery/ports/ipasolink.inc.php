<?php

echo "\nDiscovering NEC iPasolink ports...\n";

// Fetch standard IF-MIB data
$if_data = SnmpQuery::walk('IF-MIB::ifXTable')->table(1);

// Fetch vendor-specific data for NEC iPasolink devices
$ipe_system_data = SnmpQuery::walk([
    'IPE-SYSTEM-MIB::ipeCfgPortModemEnable',
    'IPE-SYSTEM-MIB::ipeStsPortEtherLinkUp',
    'IPE-SYSTEM-MIB::ipeStsPortEtherDuplex'
])->table(1);

$ipe_common_data = SnmpQuery::walk([
    'IPE-COMMON-MIB::invMacAddress',
    'IPE-COMMON-MIB::atpcPowerMode',
    'IPE-COMMON-MIB::asETHPortInterfaceType',
    'IPE-COMMON-MIB::asETHPortOperStatus'
])->table(1);

// Initialize the port_stats array
$port_stats = [];

// Process standard IF-MIB data
foreach ($if_data as $index => $port) {
    $port_stats[$index] = [
        'ifIndex' => $index,
        'ifName' => $port['IF-MIB::ifName'] ?? '',
        'ifDescr' => $port['IF-MIB::ifDescr'] ?? '',
        'ifType' => $port['IF-MIB::ifType'] ?? '',
        'ifOperStatus' => $port['IF-MIB::ifOperStatus'] ?? '',
        'ifAdminStatus' => $port['IF-MIB::ifAdminStatus'] ?? '',
        'ifPhysAddress' => $port['IF-MIB::ifPhysAddress'] ?? '',
    ];
}

// Process vendor-specific data from IPE-SYSTEM-MIB
foreach ($ipe_system_data as $index => $system_port) {
    // If the port doesn't exist in port_stats, create a basic entry
    if (!isset($port_stats[$index])) {
        $port_stats[$index] = [
            'ifIndex' => $index,
            'ifDescr' => "Port $index",
        ];
    }

    // Map vendor-specific system data
    $port_stats[$index]['ifAdminStatus'] = $system_port['IPE-SYSTEM-MIB::ipeCfgPortModemEnable'] === 'enabled' ? 'up' : 'down';
    $port_stats[$index]['ifOperStatus'] = $system_port['IPE-SYSTEM-MIB::ipeStsPortEtherLinkUp'] == 1 ? 'up' : 'down';
    $port_stats[$index]['ifDuplex'] = $system_port['IPE-SYSTEM-MIB::ipeStsPortEtherDuplex'] == 2 ? 'fullDuplex' : 'halfDuplex';
}

// Process vendor-specific data from IPE-COMMON-MIB
foreach ($ipe_common_data as $index => $common_port) {
    if (isset($port_stats[$index])) {
        $port_stats[$index]['ifPhysAddress'] = $common_port['IPE-COMMON-MIB::invMacAddress'] ?? '';
        $port_stats[$index]['ifType'] = $common_port['IPE-COMMON-MIB::asETHPortInterfaceType'] ?? 'other';
        $port_stats[$index]['ifOperStatus'] = $common_port['IPE-COMMON-MIB::asETHPortOperStatus'] == 'linkUp' ? 'up' : 'down';
    }
}

// Discover ports in LibreNMS
foreach ($port_stats as $ifIndex => $port_data) {
    discover_port($device, $port_data);
}

echo count($port_stats) . " ports created.\n";
echo "Port discovery completed.\n";