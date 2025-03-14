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

#ifndef ZABBIX_TIME_H
#define ZABBIX_TIME_H

#include "common.h"

typedef struct
{
	int	sec;	/* seconds */
	int	ns;	/* nanoseconds */
}
zbx_timespec_t;

/* time zone offset */
typedef struct
{
	char	tz_sign;	/* '+' or '-' */
	int	tz_hour;
	int	tz_min;
}
zbx_timezone_t;

#define zbx_timespec_compare(t1, t2)	\
	((t1)->sec == (t2)->sec ? (t1)->ns - (t2)->ns : (t1)->sec - (t2)->sec)

typedef enum
{
	ZBX_TIME_UNIT_UNKNOWN,
	ZBX_TIME_UNIT_SECOND,
	ZBX_TIME_UNIT_MINUTE,
	ZBX_TIME_UNIT_HOUR,
	ZBX_TIME_UNIT_DAY,
	ZBX_TIME_UNIT_WEEK,
	ZBX_TIME_UNIT_MONTH,
	ZBX_TIME_UNIT_YEAR,
	ZBX_TIME_UNIT_ISOYEAR,
	ZBX_TIME_UNIT_COUNT
}
zbx_time_unit_t;

double		zbx_time(void);
void		zbx_timespec(zbx_timespec_t *ts);
double		zbx_current_time(void);
int		zbx_is_leap_year(int year);
void		zbx_get_time(struct tm *tm, long *milliseconds, zbx_timezone_t *tz);
long		zbx_get_timezone_offset(time_t t, struct tm *tm);
struct tm	*zbx_localtime(const time_t *time, const char *tz);
int		zbx_utc_time(int year, int mon, int mday, int hour, int min, int sec, int *t);
int		zbx_day_in_month(int year, int mon);
zbx_uint64_t	zbx_get_duration_ms(const zbx_timespec_t *ts);

zbx_time_unit_t	zbx_tm_str_to_unit(const char *text);
int	zbx_tm_parse_period(const char *period, size_t *len, int *multiplier, zbx_time_unit_t *base, char **error);
void	zbx_tm_add(struct tm *tm, int multiplier, zbx_time_unit_t base);
void	zbx_tm_sub(struct tm *tm, int multiplier, zbx_time_unit_t base);
void	zbx_tm_round_up(struct tm *tm, zbx_time_unit_t base);
void	zbx_tm_round_down(struct tm *tm, zbx_time_unit_t base);
const char	*zbx_timespec_str(const zbx_timespec_t *ts);
int	zbx_get_week_number(const struct tm *tm);
int	is_time_suffix(const char *str, int *value, int length);
int	calculate_sleeptime(int nextcheck, int max_sleeptime);

char	*zbx_age2str(int age);
char	*zbx_date2str(time_t date, const char *tz);
char	*zbx_time2str(time_t time, const char *tz);
#endif /* ZABBIX_TIME_H */
