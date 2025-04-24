<?php

unset($port_stats);

echo "NEC iPasolink Port Discovery: ";

// Fetch vendor-specific data for port discovery
$entries = SnmpQuery::walk([
    'IF-MIB::ifName',
    'IPE-SYSTEM-MIB::ipeCfgPortEtherEnable',
    'IPE-SYSTEM-MIB::ipeCfgPortModemEnable',
    'IPE-SYSTEM-MIB::ipeStsPortEtherLinkUp',
    'IPE-SYSTEM-MIB::ipeStsPortEtherDuplex',
    'IPE-COMMON-MIB::invMacAddress',
    'IPE-COMMON-MIB::atpcPowerMode',
    'IPE-COMMON-MIB::modemPsOff',
    'IPE-COMMON-MIB::asETHPortInterfaceType',
    'IPE-COMMON-MIB::asETHPortSpeedDuplex',
    'IPE-COMMON-MIB::asETHPortAdminStatus',
    'IPE-COMMON-MIB::asETHPortOperStatus'
])->table(1);

// Process each port entry
foreach ($entries as $ifIndex => $entry) {
    // Determine ifType based on custom logic
    $ifType = 'other'; // Default
    if (isset($entry['IPE-SYSTEM-MIB::ipeStsPortEtherLinkUp'], $entry['IPE-COMMON-MIB::invMacAddress']) ||
        preg_match('/^(eth|bcm)/', $entry['IF-MIB::ifName'] ?? '')) {
        $ifType = 'ethernetCsmacd';
    } elseif (isset($entry['IPE-COMMON-MIB::asETHPortInterfaceType'])) {
        $ifType = mapInterfaceType($entry['IPE-COMMON-MIB::asETHPortInterfaceType']);
    } elseif (isset($entry['IPE-COMMON-MIB::atpcPowerMode'])) {
        $ifType = 'otnOdu';
    }

    // Determine ifAdminStatus
    $ifAdminStatus = 'up'; // Default
    if (isset($entry['IPE-SYSTEM-MIB::ipeCfgPortEtherEnable'])) {
        $ifAdminStatus = $entry['IPE-SYSTEM-MIB::ipeCfgPortEtherEnable'] == 1 ? 'up' : 'down';
    } elseif (isset($entry['IPE-COMMON-MIB::asETHPortAdminStatus'])) {
        $ifAdminStatus = $entry['IPE-COMMON-MIB::asETHPortAdminStatus'] == 1 ? 'up' : 'down';
    } elseif (isset($entry['IPE-SYSTEM-MIB::ipeCfgPortModemEnable'])) {
        $ifAdminStatus = $entry['IPE-SYSTEM-MIB::ipeCfgPortModemEnable'] == 1 ? 'up' : 'down';
    } elseif (isset($entry['IPE-COMMON-MIB::modemPsOff'])) {
        $ifAdminStatus = $entry['IPE-COMMON-MIB::modemPsOff'] == 2 ? 'up' : 'down';
    // } elseif (isset($entry['IPE-COMMON-MIB::atpcPowerMode'])) {
    //     $ifAdminStatus = $entry['IPE-COMMON-MIB::atpcPowerMode'] == 'active' ? 'up' : 'down';
    } elseif (str_starts_with($entry['IF-MIB::ifName'], 'lo')) {
        $ifAdminStatus = 'up';
    }

    // Determine ifOperStatus
    $ifOperStatus = 'unknown';
    if (isset($entry['IPE-SYSTEM-MIB::ipeStsPortEtherLinkUp'])) {
        $ifOperStatus = $entry['IPE-SYSTEM-MIB::ipeStsPortEtherLinkUp'] == 1 ? 'up' : 'down';
    } elseif (isset($entry['IPE-COMMON-MIB::asETHPortOperStatus'])) {
        $ifOperStatus = $entry['IPE-COMMON-MIB::asETHPortOperStatus'] == 2 ? 'up' : 'down';
    } elseif (isset($entry['IPE-SYSTEM-MIB::ipeCfgPortModemEnable'])) {
        $ifOperStatus = $entry['IPE-SYSTEM-MIB::ipeCfgPortModemEnable'] == 1 ? 'up' : 'down';
    // } elseif (isset($entry['IPE-COMMON-MIB::atpcPowerMode'])) {
    //     $ifOperStatus = $entry['IPE-COMMON-MIB::atpcPowerMode'] == 'active' ? 'up' : 'down';
    } elseif (str_starts_with($entry['IF-MIB::ifName'], 'lo')) {
        $ifOperStatus = 'up';
    }

    // Prepare port data for LibreNMS
    $port_data = [
        'ifIndex' => $ifIndex,
        'ifDescr' => $entry['IF-MIB::ifName'] ?? "Port $ifIndex",
        'ifName' => $entry['IF-MIB::ifName'] ?? "Port $ifIndex",
        'ifType' => $ifType,
        'ifOperStatus' => $ifOperStatus,
        'ifPhysAddress' => $entry['IPE-COMMON-MIB::invMacAddress'] ?? '',
        'ifAdminStatus' => $ifAdminStatus
    ];

    // Add to $port_stats for LibreNMS to process
    $port_stats[$ifIndex] = $port_data;
}

echo count($port_stats) . " ports created.\n";
echo "Port discovery completed.\n";


// Helper function to map custom interface type to standard ifType
function mapInterfaceType($customType) {
    $mapping = [
        'fiber' => 'opticalChannel',
        'copper' => 'ethernetCsmacd',
        // Add more mappings based on MIB definitions
    ];
    return $mapping[$customType] ?? 'other';
}

unset($entries);
