
# ZYXEL ES3500-8PD SNMP

## Overview

For Zabbix version: 6.2 and higher  
https://service-provider.zyxel.com/emea/en/products/carrier-and-access-switches/access-switches/mes3500-series

This template was tested on:

- ZYXEL ES3500-8PD, version V4.00(AADF.5) | 11/28/2016

## Setup

> See [Zabbix template operation](https://www.zabbix.com/documentation/6.2/manual/config/templates_out_of_the_box/network_devices) for basic instructions.

Refer to the vendor documentation.

## Zabbix configuration

No specific Zabbix configuration is required.

### Macros used

|Name|Description|Default|
|----|-----------|-------|
|{$CPU.UTIL.CRIT} |<p>-</p> |`90` |
|{$MEMORY.UTIL.MAX} |<p>-</p> |`90` |
|{$SNMP.TIMEOUT} |<p>The time interval for SNMP agent availability trigger expression.</p> |`5m` |
|{$ZYXEL.LLD.FILTER.IF.CONTROL.MATCHES} |<p>Triggers will be created only for interfaces whose description contains the value of this macro</p> |`CHANGE_IF_NEEDED` |
|{$ZYXEL.LLD.FILTER.IF.LINKUPTYPE.MATCHES} |<p>Filter of discoverable link types.</p><p>0 - Down link</p><p>1 - Cooper link</p><p>2 - Fiber link</p> |`1|2` |
|{$ZYXEL.LLD.FILTER.IF.LINKUPTYPE.NOT_MATCHES} |<p>Filter to exclude discovered by link types.</p> |`CHANGE_IF_NEEDED` |
|{$ZYXEL.LLD.FILTER.IF.NAME.MATCHES} |<p>Filter by discoverable interface names.</p> |`.*` |
|{$ZYXEL.LLD.FILTER.IF.NAME.NOT_MATCHES} |<p>Filter to exclude discovered interfaces by name.</p> |`CHANGE_IF_NEEDED` |
|{$ZYXEL.LLD.FILTER.SFP.STATUS.MATCHES} |<p>Filter of discoverable status.</p><p>0 - OK with DDM</p><p>1 - OK without DDM</p><p>2 - nonoperational</p> |`1|2` |
|{$ZYXEL.LLD.FILTER.SFP.STATUS.NOT_MATCHES} |<p>Filter to exclude discovered by status.</p> |`CHANGE_IF_NEEDED` |
|{$ZYXEL.LLD.FILTER.SFPDDM.DESC.MATCHES} |<p>Filter by discoverable SFP modules name.</p> |`.*` |
|{$ZYXEL.LLD.FILTER.SFPDDM.DESC.NOT_MATCHES} |<p>Filter to exclude discovered SFP modules by name.</p> |`N/A` |
|{$ZYXEL.LLD.SFP.UPDATE} |<p>Receiving data from the SFP module is slow, we do not recommend setting the interval less than 10 minutes.</p> |`10m` |

## Template links

There are no template links in this template.

## Discovery rules

|Name|Description|Type|Key and additional info|
|----|-----------|----|----|
|Interface discovery |<p>-</p> |SNMP |zyxel.3500_8pd.net.if.discovery<p>**Filter**:</p>AND <p>- {#ZYXEL.IF.NAME} MATCHES_REGEX `{$ZYXEL.LLD.FILTER.IF.NAME.MATCHES}`</p><p>- {#ZYXEL.IF.NAME} NOT_MATCHES_REGEX `{$ZYXEL.LLD.FILTER.IF.NAME.NOT_MATCHES}`</p><p>- {#ZYXEL.IF.LINKUPTYPE} MATCHES_REGEX `{$ZYXEL.LLD.FILTER.IF.LINKUPTYPE.MATCHES}`</p><p>- {#ZYXEL.IF.LINKUPTYPE} NOT_MATCHES_REGEX `{$ZYXEL.LLD.FILTER.IF.LINKUPTYPE.NOT_MATCHES}`</p><p>**Overrides:**</p><p>Don't create triggers for matching interface<br> - {#ZYXEL.IF.NAME} NOT_MATCHES_REGEX `{$ZYXEL.LLD.FILTER.IF.CONTROL.MATCHES}`<br>  - TRIGGER_PROTOTYPE REGEXP `.*` - NO_DISCOVER</p> |
|Memory pool discovery |<p>-</p> |SNMP |zyxel.3500_8pd.memory.discovery |
|SFP with DDM discovery |<p>SFP DDM module discovery.</p> |SNMP |zyxel.3500_8pd.sfp.ddm.discovery<p>**Preprocessing**:</p><p>- JAVASCRIPT: `The text is too long. Please see the template.`</p><p>**Filter**:</p>AND <p>- {#ZYXEL.SFP.DESCRIPTION} MATCHES_REGEX `{$ZYXEL.LLD.FILTER.SFPDDM.DESC.MATCHES}`</p><p>- {#ZYXEL.SFP.DESCRIPTION} NOT_MATCHES_REGEX `{$ZYXEL.LLD.FILTER.SFPDDM.DESC.NOT_MATCHES}`</p> |
|SFP without DDM discovery |<p>SFP module discovery.</p> |SNMP |zyxel.3500_8pd.sfp.discovery<p>**Filter**:</p>AND <p>- {#ZYXEL.SFP.STATUS} MATCHES_REGEX `{$ZYXEL.LLD.FILTER.SFP.STATUS.MATCHES}`</p><p>- {#ZYXEL.SFP.STATUS} NOT_MATCHES_REGEX `{$ZYXEL.LLD.FILTER.SFP.STATUS.NOT_MATCHES}`</p> |
|Temperature discovery |<p>An entry in tempTable.</p><p>Index of temperature unit. 1:MAC, 2:CPU, 3:PHY</p> |SNMP |zyxel.3500_8pd.temp.discovery<p>**Preprocessing**:</p><p>- JAVASCRIPT: `The text is too long. Please see the template.`</p> |
|Voltage discovery |<p>An entry in voltageTable.</p> |SNMP |zyxel.3500_8pd.volt.discovery<p>**Preprocessing**:</p><p>- JAVASCRIPT: `The text is too long. Please see the template.`</p> |

## Items collected

|Group|Name|Description|Type|Key and additional info|
|-----|----|-----------|----|---------------------|
|CPU |ZYXEL ES3500-8PD: CPU utilization |<p>MIB: ZYXEL-ES3500-8PD-MIB</p><p>Show device CPU load in %, it's the snapshot of CPU load when</p><p>getting the values.</p> |SNMP |zyxel.3500_8pd.cpuusage |
|Inventory |ZYXEL ES3500-8PD: Hardware model name |<p>MIB: RFC1213-MIB</p><p>A textual description of the entity.  This value</p><p>should include the full name and version</p><p>identification of the system's hardware type,</p><p>software operating-system, and networking</p><p>software.  It is mandatory that this only contain</p><p>printable ASCII characters.</p> |SNMP |zyxel.3500_8pd.model<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `6h`</p> |
|Inventory |ZYXEL ES3500-8PD: Contact |<p>MIB: RFC1213-MIB</p><p>The textual identification of the contact person</p><p>for this managed node, together with information</p><p>on how to contact this person.</p> |SNMP |zyxel.3500_8pd.contact<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `6h`</p> |
|Inventory |ZYXEL ES3500-8PD: Host name |<p>MIB: RFC1213-MIB</p><p>An administratively-assigned name for this</p><p>managed node.  By convention, this is the node's</p><p>fully-qualified domain name.</p> |SNMP |zyxel.3500_8pd.name<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `6h`</p> |
|Inventory |ZYXEL ES3500-8PD: Location |<p>MIB: RFC1213-MIB</p><p>The physical location of this node (e.g.,</p><p>`telephone closet, 3rd floor').</p> |SNMP |zyxel.3500_8pd.location<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `6h`</p> |
|Inventory |ZYXEL ES3500-8PD: MAC address |<p>MIB: IF-MIB</p><p>The interface's address at the protocol layer</p><p>immediately `below' the network layer in the</p><p>protocol stack.  For interfaces which do not have</p><p>such an address (e.g., a serial line), this object</p><p>should contain an octet string of zero length.</p> |SNMP |zyxel.3500_8pd.mac<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `6h`</p> |
|Inventory |ZYXEL ES3500-8PD: ZyNOS F/W Version |<p>MIB: ZYXEL-ES3500-8PD-MIB</p> |SNMP |zyxel.3500_8pd.fwversion<p>**Preprocessing**:</p><p>- JAVASCRIPT: `The text is too long. Please see the template.`</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1d`</p> |
|Inventory |ZYXEL ES3500-8PD: Hardware serial number |<p>MIB: ZYXEL-ES3500-8PD-MIB</p><p>Serial number</p> |SNMP |zyxel.3500_8pd.serialnumber<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `6h`</p> |
|Memory |ZYXEL ES3500-8PD: Memory "{#ZYXEL.MEMORY.NAME}" utilization |<p>MIB: ZYXEL-ES3500-8PD-MIB</p><p>Utilization of memory pool in %.</p> |SNMP |zyxel.3500_8pd.memory[{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1h`</p> |
|Network interfaces |ZYXEL ES3500-8PD: Port {#SNMPINDEX}: Speed Duplex |<p>MIB:  ZYXEL-ES3500-8PD-MIB</p><p>Transmission mode</p> |SNMP |zyxel.3500_8pd.net.if.speed_duplex[{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `6h`</p> |
|Network interfaces |ZYXEL ES3500-8PD: Port {#SNMPINDEX}: Interface description |<p>MIB:  ZYXEL-ES3500-8PD-MIB</p><p>A textual string containing information about the interface</p> |SNMP |zyxel.3500_8pd.net.if.name[{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `6h`</p> |
|Network interfaces |ZYXEL ES3500-8PD: Port {#SNMPINDEX}: Link type |<p>MIB:  ZYXEL-ES3500-8PD-MIB</p><p>Physical connection type</p> |SNMP |zyxel.3500_8pd.net.if.link_type[{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `6h`</p> |
|Network interfaces |ZYXEL ES3500-8PD: Port {#SNMPINDEX}: Interface name |<p>MIB: IF-MIB</p><p>A textual string containing information about the interface</p> |SNMP |zyxel.3500_8pd.net.if.descr[{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `6h`</p> |
|Network interfaces |ZYXEL ES3500-8PD: Port {#SNMPINDEX}: Operational status |<p>MIB: IF-MIB</p><p>The current operational state of the interface.</p><p>The testing(3) state indicates that no operational</p><p>packets can be passed.</p> |SNMP |zyxel.3500_8pd.net.if.operstatus[{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `6h`</p> |
|Network interfaces |ZYXEL ES3500-8PD: Port {#SNMPINDEX}: Administrative status |<p>MIB: IF-MIB</p><p>The desired state of the interface.  The</p><p>testing(3) state indicates that no operational</p><p>packets can be passed.</p> |SNMP |zyxel.3500_8pd.net.if.adminstatus[{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `6h`</p> |
|Network interfaces |ZYXEL ES3500-8PD: Port {#SNMPINDEX}: Incoming traffic |<p>MIB: IF-MIB</p><p>The total number of octets received on the interface,</p><p>including framing characters.</p> |SNMP |zyxel.3500_8pd.net.if.in.traffic[{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- MULTIPLIER: `8`</p><p>- CHANGE_PER_SECOND</p> |
|Network interfaces |ZYXEL ES3500-8PD: Port {#SNMPINDEX}: Incoming unicast packages |<p>MIB: IF-MIB</p><p>The number of packets, delivered by this sub-layer to a</p><p>higher (sub-)layer, which were not addressed to a multicast</p><p>or broadcast address at this sub-layer</p> |SNMP |zyxel.3500_8pd.net.if.in.ucastpkts[{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- CHANGE_PER_SECOND</p> |
|Network interfaces |ZYXEL ES3500-8PD: Port {#SNMPINDEX}: Incoming multicast packages |<p>MIB: IF-MIB</p><p>The number of packets, delivered by this sub-layer to a</p><p>higher (sub-)layer, which were addressed to a multicast</p><p>address at this sub-layer.  For a MAC layer protocol, this</p><p>includes both Group and Functional addresses.</p> |SNMP |zyxel.3500_8pd.net.if.in.multicastpkts[{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- CHANGE_PER_SECOND</p> |
|Network interfaces |ZYXEL ES3500-8PD: Port {#SNMPINDEX}: Incoming broadcast packages |<p>MIB: IF-MIB</p><p>The number of packets, delivered by this sub-layer to a</p><p>higher (sub-)layer, which were addressed to a broadcast</p><p>address at this sub-layer.</p> |SNMP |zyxel.3500_8pd.net.if.in.broadcastpkts[{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- CHANGE_PER_SECOND</p> |
|Network interfaces |ZYXEL ES3500-8PD: Port {#SNMPINDEX}: Outgoing traffic |<p>MIB: IF-MIB</p><p>The total number of octets transmitted out of the</p><p>interface, including framing characters.  This object is a</p><p>64-bit version of ifOutOctets.</p> |SNMP |zyxel.3500_8pd.net.if.out.traffic[{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- MULTIPLIER: `8`</p><p>- CHANGE_PER_SECOND</p> |
|Network interfaces |ZYXEL ES3500-8PD: Port {#SNMPINDEX}: Outgoing unicast packages |<p>MIB: IF-MIB</p><p>The total number of packets that higher-level protocols</p><p>requested be transmitted, and which were not addressed to a</p><p>multicast or broadcast address at this sub-layer, including</p><p>those that were discarded or not sent.</p> |SNMP |zyxel.3500_8pd.net.if.out.ucastpkts[{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- CHANGE_PER_SECOND</p> |
|Network interfaces |ZYXEL ES3500-8PD: Port {#SNMPINDEX}: Outgoing multicast packages |<p>MIB: IF-MIB</p><p>The total number of packets that higher-level protocols</p><p>requested be transmitted, and which were addressed to a</p><p>multicast address at this sub-layer, including those that</p><p>were discarded or not sent.  For a MAC layer protocol, this</p><p>includes both Group and Functional addresses.</p> |SNMP |zyxel.3500_8pd.net.if.out.multicastpkts[{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- CHANGE_PER_SECOND</p> |
|Network interfaces |ZYXEL ES3500-8PD: Port {#SNMPINDEX}: Outgoing broadcast packages |<p>MIB: IF-MIB</p><p>The total number of packets that higher-level protocols</p><p>requested be transmitted, and which were addressed to a</p><p>broadcast address at this sub-layer, including those that</p><p>were discarded or not sent.</p> |SNMP |zyxel.3500_8pd.net.if.out.broadcastpkts[{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- CHANGE_PER_SECOND</p> |
|Network interfaces |ZYXEL ES3500-8PD: Port {#SNMPINDEX}: Link speed |<p>MIB: IF-MIB</p><p>An estimate of the interface's current bandwidth in bits per second</p> |SNMP |zyxel.3500_8pd.net.if.highspeed[{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- MULTIPLIER: `1000000`</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `6h`</p> |
|Network interfaces |ZYXEL ES3500-8PD: Port {#SNMPINDEX}: Incoming utilization |<p>Interface utilization percentage</p> |CALCULATED |zyxel.3500_8pd.net.if.in.util[{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- IN_RANGE: `0 100`</p><p>⛔️ON_FAIL: `DISCARD_VALUE -> `</p><p>- JAVASCRIPT: `return +parseFloat(value).toFixed(0); `</p><p>**Expression**:</p>`last(//zyxel.3500_8pd.net.if.in.traffic[{#SNMPINDEX}]) * (last(//zyxel.3500_8pd.net.if.highspeed[{#SNMPINDEX}]) <> 0) / ( last(//zyxel.3500_8pd.net.if.highspeed[{#SNMPINDEX}]) + (last(//zyxel.3500_8pd.net.if.highspeed[{#SNMPINDEX}]) = 0) ) * 100` |
|Network interfaces |ZYXEL ES3500-8PD: Port {#SNMPINDEX}: Outgoing utilization |<p>Interface utilization percentage</p> |CALCULATED |zyxel.3500_8pd.net.if.out.util[{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- IN_RANGE: `0 100`</p><p>⛔️ON_FAIL: `DISCARD_VALUE -> `</p><p>- JAVASCRIPT: `return +parseFloat(value).toFixed(0); `</p><p>**Expression**:</p>`last(//zyxel.3500_8pd.net.if.out.traffic[{#SNMPINDEX}]) * (last(//zyxel.3500_8pd.net.if.highspeed[{#SNMPINDEX}]) <> 0) / ( last(//zyxel.3500_8pd.net.if.highspeed[{#SNMPINDEX}]) + (last(//zyxel.3500_8pd.net.if.highspeed[{#SNMPINDEX}]) = 0) ) * 100` |
|Network interfaces |ZYXEL ES3500-8PD: SFP {#SNMPINDEX}: Status |<p>MIB: ZYXEL-ES3500-8PD-MIB</p><p>Transceiver module status.</p> |SNMP |zyxel.3500_8pd.sfp.status[{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1h`</p> |
|Network interfaces |ZYXEL ES3500-8PD: SFP {#SNMPINDEX}: Vendor |<p>MIB: ZYXEL-ES3500-8PD-MIB</p><p>Transceiver module vendor name.</p> |SNMP |zyxel.3500_8pd.sfp.vendor[{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1h`</p> |
|Network interfaces |ZYXEL ES3500-8PD: SFP {#SNMPINDEX}: Part number |<p>MIB: ZYXEL-ES3500-8PD-MIB</p><p>Part number provided by transceiver module vendor.</p> |SNMP |zyxel.3500_8pd.sfp.part[{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1h`</p> |
|Network interfaces |ZYXEL ES3500-8PD: SFP {#SNMPINDEX}: Serial number |<p>MIB: ZYXEL-ES3500-8PD-MIB</p><p>Serial number provided by transceiver module vendor.</p> |SNMP |zyxel.3500_8pd.sfp.serialnumber[{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1h`</p> |
|Network interfaces |ZYXEL ES3500-8PD: SFP {#SNMPINDEX}: Revision |<p>MIB: ZYXEL-ES3500-8PD-MIB</p><p>Revision level for part number provided by transceiver module vendor.</p> |SNMP |zyxel.3500_8pd.sfp.revision[{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1h`</p> |
|Network interfaces |ZYXEL ES3500-8PD: SFP {#SNMPINDEX}: Date code |<p>MIB: ZYXEL-ES3500-8PD-MIB</p><p>Transceiver module vendor's manufacturing date code.</p> |SNMP |zyxel.3500_8pd.sfp.datecode[{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1h`</p> |
|Network interfaces |ZYXEL ES3500-8PD: SFP {#SNMPINDEX}: Transceiver |<p>MIB: ZYXEL-ES3500-8PD-MIB</p><p>Transceiver module type names.</p> |SNMP |zyxel.3500_8pd.sfp.transceiver[{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1h`</p> |
|Network interfaces |ZYXEL ES3500-8PD: SFP {#ZYXEL.SFP.PORT}: {#ZYXEL.SFP.DESCRIPTION} |<p>MIB: ZYXEL-ES3500-8PD-MIB</p><p>Transceiver module DDM data ({#ZYXEL.SFP.DESCRIPTION}).</p> |SNMP |zyxel.3500_8pd.sfp.ddm[{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- MULTIPLIER: `0.01`</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `6h`</p> |
|Power supply |ZYXEL ES3500-8PD: Nominal "{#ZYXEL.VOLT.NOMINAL}" |<p>MIB: ZYXEL-ES3500-8PD-MIB</p><p>The current voltage reading.</p> |SNMP |zyxel.3500_8pd.volt[{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- MULTIPLIER: `0.001`</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1h`</p> |
|Status |ZYXEL ES3500-8PD: SNMP agent availability |<p>-</p> |INTERNAL |zabbix[host,snmp,available]<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1h`</p> |
|Status |ZYXEL ES3500-8PD: Uptime |<p>MIB: RFC1213-MIB</p><p>The time (in hundredths of a second) since the</p><p>network management portion of the system was last</p><p>re-initialized.</p> |SNMP |zyxel.3500_8pd.uptime<p>**Preprocessing**:</p><p>- MULTIPLIER: `0.01`</p> |
|Temperature |ZYXEL ES3500-8PD: Temperature "{#ZYXEL.TEMP.ID}" |<p>MIB: ZYXEL-ES3500-8PD-MIB</p><p>The current temperature measured at this sensor</p> |SNMP |zyxel.3500_8pd.temp[{#SNMPINDEX}]<p>**Preprocessing**:</p><p>- DISCARD_UNCHANGED_HEARTBEAT: `1h`</p> |

## Triggers

|Name|Description|Expression|Severity|Dependencies and additional info|
|----|-----------|----|----|----|
|ZYXEL ES3500-8PD: High CPU utilization |<p>CPU utilization is too high. The system might be slow to respond.</p> |`min(/ZYXEL ES3500-8PD SNMP/zyxel.3500_8pd.cpuusage,5m)>{$CPU.UTIL.CRIT}` |WARNING | |
|ZYXEL ES3500-8PD: Template does not match hardware |<p>This template is for Zyxel ES3500-8PD, but connected to {ITEM.VALUE}</p> |`last(/ZYXEL ES3500-8PD SNMP/zyxel.3500_8pd.model)<>"ES3500-8PD"` |INFO | |
|ZYXEL ES3500-8PD: Firmware has changed |<p>Firmware version has changed. Ack to close</p> |`last(/ZYXEL ES3500-8PD SNMP/zyxel.3500_8pd.fwversion,#1)<>last(/ZYXEL ES3500-8PD SNMP/zyxel.3500_8pd.fwversion,#2) and length(last(/ZYXEL ES3500-8PD SNMP/zyxel.3500_8pd.fwversion))>0` |INFO |<p>Manual close: YES</p> |
|ZYXEL ES3500-8PD: Device has been replaced |<p>Device serial number has changed. Ack to close</p> |`last(/ZYXEL ES3500-8PD SNMP/zyxel.3500_8pd.serialnumber,#1)<>last(/ZYXEL ES3500-8PD SNMP/zyxel.3500_8pd.serialnumber,#2) and length(last(/ZYXEL ES3500-8PD SNMP/zyxel.3500_8pd.serialnumber))>0` |INFO |<p>Manual close: YES</p> |
|ZYXEL ES3500-8PD: High memory utilization in "{#ZYXEL.MEMORY.NAME}" pool |<p>The system is running out of free memory.</p> |`min(/ZYXEL ES3500-8PD SNMP/zyxel.3500_8pd.memory[{#SNMPINDEX}],5m)>{$MEMORY.UTIL.MAX}` |AVERAGE | |
|ZYXEL ES3500-8PD: Port {#SNMPINDEX}: Link down |<p>This trigger expression works as follows:</p><p>1. Can be triggered if operations status is down.</p><p>2. {TEMPLATE_NAME:METRIC.diff()}=1) - trigger fires only if operational status was up(1) sometime before. (So, do not fire 'ethernal off' interfaces.)</p><p>WARNING: if closed manually - won't fire again on next poll, because of .diff.</p> |`last(/ZYXEL ES3500-8PD SNMP/zyxel.3500_8pd.net.if.operstatus[{#SNMPINDEX}])=2 and last(/ZYXEL ES3500-8PD SNMP/zyxel.3500_8pd.net.if.operstatus[{#SNMPINDEX}],#1)<>last(/ZYXEL ES3500-8PD SNMP/zyxel.3500_8pd.net.if.operstatus[{#SNMPINDEX}],#2)`<p>Recovery expression:</p>`last(/ZYXEL ES3500-8PD SNMP/zyxel.3500_8pd.net.if.operstatus[{#SNMPINDEX}])<>2` |AVERAGE |<p>Manual close: YES</p> |
|ZYXEL ES3500-8PD: SFP {#SNMPINDEX} has been replaced |<p>SFP {#SNMPINDEX} serial number has changed. Ack to close</p> |`last(/ZYXEL ES3500-8PD SNMP/zyxel.3500_8pd.sfp.serialnumber[{#SNMPINDEX}],#1)<>last(/ZYXEL ES3500-8PD SNMP/zyxel.3500_8pd.sfp.serialnumber[{#SNMPINDEX}],#2) and length(last(/ZYXEL ES3500-8PD SNMP/zyxel.3500_8pd.sfp.serialnumber[{#SNMPINDEX}]))>0` |INFO |<p>Manual close: YES</p> |
|ZYXEL ES3500-8PD: SFP {#ZYXEL.SFP.PORT}: High {#ZYXEL.SFP.DESCRIPTION} |<p>The upper threshold value of the parameter is exceeded</p> |`last(/ZYXEL ES3500-8PD SNMP/zyxel.3500_8pd.sfp.ddm[{#SNMPINDEX}]) > {#ZYXEL.SFP.WARN.MAX}` |WARNING | |
|ZYXEL ES3500-8PD: SFP {#ZYXEL.SFP.PORT}: Low {#ZYXEL.SFP.DESCRIPTION} |<p>The parameter values are less than the lower threshold</p> |`last(/ZYXEL ES3500-8PD SNMP/zyxel.3500_8pd.sfp.ddm[{#SNMPINDEX}]) < {#ZYXEL.SFP.WARN.MIN}` |WARNING | |
|ZYXEL ES3500-8PD: Voltage {#ZYXEL.VOLT.NOMINAL} is in critical state |<p>Please check the power supply</p> |`last(/ZYXEL ES3500-8PD SNMP/zyxel.3500_8pd.volt[{#SNMPINDEX}])<{#ZYXEL.VOLT.THRESH.LOW}` |AVERAGE | |
|ZYXEL ES3500-8PD: No SNMP data collection |<p>SNMP is not available for polling. Please check device connectivity and SNMP settings.</p> |`max(/ZYXEL ES3500-8PD SNMP/zabbix[host,snmp,available],{$SNMP.TIMEOUT})=0` |WARNING | |
|ZYXEL ES3500-8PD: has been restarted |<p>Uptime is less than 10 minutes.</p> |`last(/ZYXEL ES3500-8PD SNMP/zyxel.3500_8pd.uptime)<10m` |INFO |<p>Manual close: YES</p> |
|ZYXEL ES3500-8PD: Temperature {#ZYXEL.TEMP.ID} is in critical state |<p>Please check the temperature</p> |`last(/ZYXEL ES3500-8PD SNMP/zyxel.3500_8pd.temp[{#SNMPINDEX}])>{#ZYXEL.TEMP.THRESH.HIGH}` |AVERAGE | |

## Feedback

Please report any issues with the template at https://support.zabbix.com

You can also provide feedback, discuss the template or ask for help with it at [ZABBIX forums](https://www.zabbix.com/forum/zabbix-suggestions-and-feedback/422668-discussion-thread-for-official-zabbix-templates-for-zyxel).

## Known Issues

- Description: Incorrect handling of SNMP bulk requests. Disable the use of bulk requests in the SNMP interface settings.
  - Version: all versions firmware
  - Device: ZYXEL ES3500-8PD

