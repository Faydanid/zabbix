/*
** Zabbix
** Copyright (C) 2001-2022 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

#ifndef ZABBIX_VERSION_H
#define ZABBIX_VERSION_H

#define ZBX_COMPONENT_VERSION(major, minor)	((major << 16) | minor)
#define ZBX_COMPONENT_VERSION_MAJOR(version)	(version >> 16)
#define ZBX_COMPONENT_VERSION_MINOR(version)	(version & 0xFFFF)

#define ZBX_STR2(str)	#str
#define ZBX_STR(str)	ZBX_STR2(str)

#define APPLICATION_NAME	"Zabbix Agent"
#define ZABBIX_REVDATE		"4 July 2022"
#define ZABBIX_VERSION_MAJOR	6
#define ZABBIX_VERSION_MINOR	4
#define ZABBIX_VERSION_PATCH	0
#ifndef ZABBIX_VERSION_REVISION
#	define ZABBIX_VERSION_REVISION	{ZABBIX_REVISION}
#endif
#ifdef _WINDOWS
#	ifndef ZABBIX_VERSION_RC_NUM
#		define ZABBIX_VERSION_RC_NUM	{ZABBIX_RC_NUM}
#	endif
#endif
#define ZABBIX_VERSION_RC	"alpha1"
#define ZABBIX_VERSION		ZBX_STR(ZABBIX_VERSION_MAJOR) "." ZBX_STR(ZABBIX_VERSION_MINOR) "." \
				ZBX_STR(ZABBIX_VERSION_PATCH) ZABBIX_VERSION_RC
#define ZABBIX_REVISION		ZBX_STR(ZABBIX_VERSION_REVISION)

int	zbx_get_component_version(char *value);
#endif /* ZABBIX_VERSION_H */
