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

#ifndef ZABBIX_EXPR_H
#define ZABBIX_EXPR_H

#include "common.h"

int	zbx_function_validate(const char *expr, size_t *par_l, size_t *par_r, char *error, int max_error_len);
int	zbx_function_validate_parameters(const char *expr, size_t *length);
int	zbx_user_macro_parse(const char *macro, int *macro_r, int *context_l, int *context_r,
		unsigned char *context_op);
int	zbx_user_macro_parse_dyn(const char *macro, char **name, char **context, int *length,
		unsigned char *context_op);
char	*zbx_user_macro_unquote_context_dyn(const char *context, int len);
char	*zbx_user_macro_quote_context_dyn(const char *context, int force_quote, char **error);
int	zbx_function_find(const char *expr, size_t *func_pos, size_t *par_l, size_t *par_r, char *error,
		int max_error_len);
void	zbx_function_param_parse(const char *expr, size_t *param_pos, size_t *length, size_t *sep_pos);
char	*zbx_function_param_unquote_dyn(const char *param, size_t len, int *quoted);
int	zbx_function_param_quote(char **param, int forced);
char	*zbx_function_get_param_dyn(const char *params, int Nparam);

typedef enum
{
	ZBX_FUNCTION_TYPE_UNKNOWN,
	ZBX_FUNCTION_TYPE_HISTORY,
	ZBX_FUNCTION_TYPE_TIMER,
	ZBX_FUNCTION_TYPE_TRENDS
}
zbx_function_type_t;

zbx_function_type_t	zbx_get_function_type(const char *func);

int	zbx_is_double_suffix(const char *str, unsigned char flags);
double	str2double(const char *str);
int	zbx_suffixed_number_parse(const char *number, int *len);
int	zbx_strmatch_condition(const char *value, const char *pattern, unsigned char op);

/* token START */
/* tokens used in expressions */
#define ZBX_TOKEN_OBJECTID		0x00001
#define ZBX_TOKEN_MACRO			0x00002
#define ZBX_TOKEN_LLD_MACRO		0x00004
#define ZBX_TOKEN_USER_MACRO		0x00008
#define ZBX_TOKEN_FUNC_MACRO		0x00010
#define ZBX_TOKEN_SIMPLE_MACRO		0x00020
#define ZBX_TOKEN_REFERENCE		0x00040
#define ZBX_TOKEN_LLD_FUNC_MACRO	0x00080
#define ZBX_TOKEN_EXPRESSION_MACRO	0x00100

/* additional token flags */
#define ZBX_TOKEN_JSON		0x0010000
#define ZBX_TOKEN_REGEXP	0x0040000
#define ZBX_TOKEN_XPATH		0x0080000
#define ZBX_TOKEN_REGEXP_OUTPUT	0x0100000
#define ZBX_TOKEN_PROMETHEUS	0x0200000
#define ZBX_TOKEN_JSONPATH	0x0400000
#define ZBX_TOKEN_STR_REPLACE	0x0800000
#define ZBX_TOKEN_STRING	0x1000000

/* location of a substring */
typedef struct
{
	/* left position */
	size_t	l;
	/* right position */
	size_t	r;
}
zbx_strloc_t;

/* data used by macros, lld macros and objectid tokens */
typedef struct
{
	zbx_strloc_t	name;
}
zbx_token_macro_t;

/* data used by macros, lld macros and objectid tokens */
typedef struct
{
	zbx_strloc_t	expression;
}
zbx_token_expression_macro_t;

/* data used by user macros */
typedef struct
{
	/* macro name */
	zbx_strloc_t	name;
	/* macro context, for macros without context the context.l and context.r fields are set to 0 */
	zbx_strloc_t	context;
}
zbx_token_user_macro_t;

/* data used by macro functions */
typedef struct
{
	/* the macro including the opening and closing brackets {}, for example: {ITEM.VALUE} */
	zbx_strloc_t	macro;
	/* function + parameters, for example: regsub("([0-9]+)", \1) */
	zbx_strloc_t	func;
	/* parameters, for example: ("([0-9]+)", \1) */
	zbx_strloc_t	func_param;
}
zbx_token_func_macro_t;

/* data used by simple (host:key) macros */
typedef struct
{
	/* host name, supporting simple macros as a host name, for example Zabbix server or {HOST.HOST} */
	zbx_strloc_t	host;
	/* key + parameters, supporting {ITEM.KEYn} macro, for example system.uname or {ITEM.KEY1}  */
	zbx_strloc_t	key;
	/* function + parameters, for example avg(5m) */
	zbx_strloc_t	func;
	/* parameters, for example (5m) */
	zbx_strloc_t	func_param;
}
zbx_token_simple_macro_t;

/* data used by references */
typedef struct
{
	/* index of constant being referenced (1 for $1, 2 for $2, ..., 9 for $9) */
	int	index;
}
zbx_token_reference_t;

/* the token type specific data */
typedef union
{
	zbx_token_macro_t		objectid;
	zbx_token_macro_t		macro;
	zbx_token_macro_t		lld_macro;
	zbx_token_expression_macro_t	expression_macro;
	zbx_token_user_macro_t		user_macro;
	zbx_token_func_macro_t		func_macro;
	zbx_token_func_macro_t		lld_func_macro;
	zbx_token_simple_macro_t	simple_macro;
	zbx_token_reference_t		reference;
}
zbx_token_data_t;

/* {} token data */
typedef struct
{
	/* token type, see ZBX_TOKEN_ defines */
	int			type;
	/* the token location in expression including opening and closing brackets {} */
	zbx_strloc_t		loc;
	/* the token type specific data */
	zbx_token_data_t	data;
}
zbx_token_t;

#define ZBX_TOKEN_SEARCH_BASIC			0x00
#define ZBX_TOKEN_SEARCH_REFERENCES		0x01
#define ZBX_TOKEN_SEARCH_EXPRESSION_MACRO	0x02
#define ZBX_TOKEN_SEARCH_FUNCTIONID		0x04
#define ZBX_TOKEN_SEARCH_SIMPLE_MACRO		0x08	/* used by the upgrade patches only */

typedef int zbx_token_search_t;

int	zbx_token_find(const char *expression, int pos, zbx_token_t *token, zbx_token_search_t token_search);

int	zbx_token_parse_user_macro(const char *expression, const char *macro, zbx_token_t *token);
int	zbx_token_parse_macro(const char *expression, const char *macro, zbx_token_t *token);
int	zbx_token_parse_objectid(const char *expression, const char *macro, zbx_token_t *token);
int	zbx_token_parse_lld_macro(const char *expression, const char *macro, zbx_token_t *token);
int	zbx_token_parse_nested_macro(const char *expression, const char *macro, int simple_macro_find,
		zbx_token_t *token);
/* token END */

/* report scheduling */

#define ZBX_REPORT_CYCLE_DAILY		0
#define ZBX_REPORT_CYCLE_WEEKLY		1
#define ZBX_REPORT_CYCLE_MONTHLY	2
#define ZBX_REPORT_CYCLE_YEARLY		3

int	zbx_get_agent_item_nextcheck(zbx_uint64_t itemid, const char *delay, int now,
		int *nextcheck, int *scheduling, char **error);

/* interval START */
typedef struct zbx_custom_interval	zbx_custom_interval_t;
int	zbx_interval_preproc(const char *interval_str, int *simple_interval, zbx_custom_interval_t **custom_intervals,
		char **error);
int	zbx_validate_interval(const char *str, char **error);
int	zbx_custom_interval_is_scheduling(const zbx_custom_interval_t *custom_intervals);
void	zbx_custom_interval_free(zbx_custom_interval_t *custom_intervals);
int	calculate_item_nextcheck(zbx_uint64_t seed, int item_type, int simple_interval,
		const zbx_custom_interval_t *custom_intervals, time_t now);
int	calculate_item_nextcheck_unreachable(int simple_interval, const zbx_custom_interval_t *custom_intervals,
		time_t disable_until);

int	zbx_check_time_period(const char *period, time_t time, const char *tz, int *res);
int	zbx_get_report_nextcheck(int now, unsigned char cycle, unsigned char weekdays, int start_time,
		const char *tz);
/* interval END */
#endif /* ZABBIX_EXPR_H */
