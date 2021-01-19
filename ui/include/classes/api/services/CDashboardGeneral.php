<?php
/*
** Zabbix
** Copyright (C) 2001-2021 Zabbix SIA
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


/**
 * Common class for dashboards API and template dashboards API.
 */
abstract class CDashboardGeneral extends CApiService {

	protected const MAX_X = 23; // DASHBOARD_MAX_COLUMNS - 1;
	protected const MAX_Y = 62; // DASHBOARD_MAX_ROWS - 2;
	protected const DISPLAY_PERIODS = [10, 30, 60, 120, 600, 1800, 3600];

	protected const WIDGET_FIELD_TYPE_COLUMNS_FK = [
		ZBX_WIDGET_FIELD_TYPE_GROUP => 'value_groupid',
		ZBX_WIDGET_FIELD_TYPE_HOST => 'value_hostid',
		ZBX_WIDGET_FIELD_TYPE_ITEM => 'value_itemid',
		ZBX_WIDGET_FIELD_TYPE_ITEM_PROTOTYPE => 'value_itemid',
		ZBX_WIDGET_FIELD_TYPE_GRAPH => 'value_graphid',
		ZBX_WIDGET_FIELD_TYPE_GRAPH_PROTOTYPE => 'value_graphid',
		ZBX_WIDGET_FIELD_TYPE_MAP => 'value_sysmapid'
	];

	protected const WIDGET_FIELD_TYPE_COLUMNS = [
		ZBX_WIDGET_FIELD_TYPE_INT32 => 'value_int',
		ZBX_WIDGET_FIELD_TYPE_STR => 'value_str',
	] + self::WIDGET_FIELD_TYPE_COLUMNS_FK;

	protected $tableName = 'dashboard';
	protected $tableAlias = 'd';
	protected $sortColumns = ['dashboardid', 'name'];

	/**
	 * @param array $options
	 *
	 * @throws APIException if the input is invalid.
	 *
	 * @return array|int
	 */
	abstract public function get(array $options = []);

	/**
	 * @param array $dashboards
	 *
	 * @throws APIException if the input is invalid.
	 */
	abstract protected function validateCreate(array &$dashboards): void;

	/**
	 * @param array      $dashboards
	 * @param array|null $db_dashboards
	 *
	 * @throws APIException if the input is invalid.
	 */
	abstract protected function validateUpdate(array &$dashboards, array &$db_dashboards = null): void;

	/**
	 * @param array $dashboards
	 *
	 * @return array
	 */
	public function create(array $dashboards): array {
		$this->validateCreate($dashboards);

		$ins_dashboards = [];

		foreach ($dashboards as $dashboard) {
			unset($dashboard['users'], $dashboard['userGroups'], $dashboard['pages']);
			$ins_dashboards[] = $dashboard;
		}

		$dashboardids = DB::insert('dashboard', $ins_dashboards);

		foreach ($dashboards as $index => &$dashboard) {
			$dashboard['dashboardid'] = $dashboardids[$index];
		}
		unset($dashboard);

		if ($this instanceof CDashboard) {
			$this->updateDashboardUser($dashboards, false);
			$this->updateDashboardUsrgrp($dashboards, false);
		}

		$this->updatePages($dashboards);

		$this->addAuditBulk(AUDIT_ACTION_ADD, static::AUDIT_RESOURCE, $dashboards);

		return ['dashboardids' => $dashboardids];
	}

	/**
	 * @param array $dashboards
	 *
	 * @return array
	 */
	public function update(array $dashboards): array {
		$this->validateUpdate($dashboards, $db_dashboards);

		$upd_dashboards = [];

		foreach ($dashboards as $dashboard) {
			$upd_dashboard = dbUpdatedValues('dashboard', $dashboard, $db_dashboards[$dashboard['dashboardid']]);

			if ($upd_dashboard) {
				$upd_dashboards[] = [
					'values' => $upd_dashboard,
					'where' => ['dashboardid' => $dashboard['dashboardid']]
				];
			}
		}

		if ($upd_dashboards) {
			DB::update('dashboard', $upd_dashboards);
		}

		if ($this instanceof CDashboard) {
			$this->updateDashboardUser($dashboards, true);
			$this->updateDashboardUsrgrp($dashboards, true);
		}

		$this->updatePages($dashboards, $db_dashboards);

		$this->addAuditBulk(AUDIT_ACTION_UPDATE, static::AUDIT_RESOURCE, $dashboards, $db_dashboards);

		return ['dashboardids' => array_column($dashboards, 'dashboardid')];
	}

	/**
	 * @param array $dashboardids
	 *
	 * @throws APIException if the input is invalid.
	 *
	 * @return array
	 */
	public function delete(array $dashboardids): array {
		$api_input_rules = ['type' => API_IDS, 'flags' => API_NOT_EMPTY, 'uniq' => true];

		if (!CApiInputValidator::validate($api_input_rules, $dashboardids, '/', $error)) {
			self::exception(ZBX_API_ERROR_PARAMETERS, $error);
		}

		$db_dashboards = $this->get([
			'output' => ['dashboardid', 'name'],
			'dashboardids' => $dashboardids,
			'editable' => true,
			'preservekeys' => true
		]);

		if (count($db_dashboards) != count($dashboardids)) {
			self::exception(ZBX_API_ERROR_PERMISSIONS, _('No permissions to referred object or it does not exist!'));
		}

		$db_pages = DB::select('dashboard_page', [
			'output' => [],
			'filter' => ['dashboardid' => $dashboardids],
			'preservekeys' => true
		]);

		if ($db_pages) {
			$db_widgets = DB::select('widget', [
				'output' => [],
				'filter' => ['dashboard_pageid' => array_keys($db_pages)],
				'preservekeys' => true
			]);

			if ($db_widgets) {
				self::deleteWidgets(array_keys($db_widgets));
			}

			DB::delete('dashboard_page', ['dashboard_pageid' => array_keys($db_pages)]);
		}

		DB::delete('dashboard', ['dashboardid' => $dashboardids]);

		$this->addAuditBulk(AUDIT_ACTION_DELETE, static::AUDIT_RESOURCE, $db_dashboards);

		return ['dashboardids' => $dashboardids];
	}

	/**
	 * Add existing pages, widgets and widget fields to $db_dashboards whether these are affected by the update.
	 *
	 * @param array      $dashboards
	 * @param array|null $db_dashboards
	 */
	protected function addAffectedObjects(array $dashboards, array &$db_dashboards): void {
		// Parent ID criteria for fetching child objects - pages, widgets and fields.
		$dashboardids = [];
		$pageids = [];
		$widgetids = [];

		// The requested parent-child relations.
		$page_parents = [];
		$widget_parents = [];

		foreach ($dashboards as $dashboard) {
			$dashboardid = $dashboard['dashboardid'];

			// Updating dashboard pages?
			if (array_key_exists('pages', $dashboard)) {
				// Fetch all pages of this dashboard.
				$dashboardids[$dashboardid] = true;

				foreach ($dashboard['pages'] as $page) {
					if (array_key_exists('dashboard_pageid', $page)) {
						$pageid = $page['dashboard_pageid'];
						$page_parents[$pageid] = $dashboardid;

						// Updating page widgets?
						if (array_key_exists('widgets', $page)) {
							// Fetch all widgets of this page.
							$pageids[$pageid] = true;

							foreach ($page['widgets'] as $widget) {
								if (array_key_exists('widgetid', $widget)) {
									$widgetid = $widget['widgetid'];
									$widget_parents[$widgetid] = $pageid;

									// Updating widget fields?
									if (array_key_exists('fields', $widget)) {
										// Fetch all fields of this widget.
										$widgetids[$widgetid] = true;
									}
								}
							}
						}
					}
					else {
						// Page widgets can't have IDs specified if the page itself didn't have one.
						if (array_key_exists('widgets', $page) && array_column($page['widgets'], 'widgetid')) {
							self::exception(ZBX_API_ERROR_PERMISSIONS,
								_('No permissions to referred object or it does not exist!')
							);
						}
					}
				}
			}
		}

		foreach ($db_dashboards as &$db_dashboard) {
			$db_dashboard['pages'] = [];
		}
		unset($db_dashboard);

		if ($dashboardids) {
			$db_pages = DB::select('dashboard_page', [
				'output' => array_keys(DB::getSchema('dashboard_page')['fields']),
				'filter' => ['dashboardid' => array_keys($dashboardids)],
				'preservekeys' => true
			]);

			foreach ($page_parents as $pageid => $dashboardid) {
				if (!array_key_exists($pageid, $db_pages)
						|| bccomp($db_pages[$pageid]['dashboardid'], $dashboardid) != 0) {
					self::exception(ZBX_API_ERROR_PERMISSIONS,
						_('No permissions to referred object or it does not exist!')
					);
				}
			}

			foreach ($db_pages as &$db_page) {
				$db_page['widgets'] = [];
			}
			unset($db_page);

			if ($pageids) {
				$db_widgets = DB::select('widget', [
					'output' => array_keys(DB::getSchema('widget')['fields']),
					'filter' => ['dashboard_pageid' => array_keys($pageids)],
					'preservekeys' => true
				]);

				foreach ($widget_parents as $widgetid => $pageid) {
					if (!array_key_exists($widgetid, $db_widgets)
							|| bccomp($db_widgets[$widgetid]['dashboard_pageid'], $pageid) != 0) {
						self::exception(ZBX_API_ERROR_PERMISSIONS,
							_('No permissions to referred object or it does not exist!')
						);
					}
				}

				foreach ($db_widgets as &$db_widget) {
					$db_widget['fields'] = [];
				}
				unset($db_widget);

				if ($widgetids) {
					$db_fields = DB::select('widget_field', [
						'output' => array_keys(DB::getSchema('widget_field')['fields']),
						'filter' => ['widgetid' => array_keys($widgetids)],
						'preservekeys' => true
					]);

					foreach ($db_fields as $fieldid => $db_field) {
						$db_widgets[$db_field['widgetid']]['fields'][$fieldid] = $db_field + [
							'value' => $db_field[self::WIDGET_FIELD_TYPE_COLUMNS[$db_field['type']]]
						];
					}
				}

				foreach ($db_widgets as $widgetid => $db_widget) {
					$db_pages[$db_widget['dashboard_pageid']]['widgets'][$widgetid] = $db_widget;
				}
			}

			foreach ($db_pages as $pageid => $db_page) {
				$db_dashboards[$db_page['dashboardid']]['pages'][$pageid] = $db_page;
			}
		}
	}

	/**
	 * Check for unique dashboard names.
	 *
	 * @param array      $dashboards
	 * @param array|null $db_dashboards
	 *
	 * @throws APIException if dashboard names are not unique.
	 */
	protected function checkDuplicates(array $dashboards, array $db_dashboards = null): void {
		$criteria = [];

		foreach ($dashboards as $dashboard) {
			$name = null;
			$dashboardid = null;

			if ($db_dashboards === null) {
				$name = $dashboard['name'];
			}
			elseif ($dashboard['name'] !== $db_dashboards[$dashboard['dashboardid']]['name']) {
				$name = $dashboard['name'];
				$dashboardid = $dashboard['dashboardid'];
			}

			if ($name !== null) {
				$templateid = ($this instanceof CTemplateDashboard) ? $dashboard['templateid'] : 0;

				if (!array_key_exists($templateid, $criteria)) {
					$criteria[$templateid] = ['names' => [], 'dashboardids' => []];
				}

				$criteria[$templateid]['names'][] = $name;

				if ($dashboardid !== null) {
					$criteria[$templateid]['dashboardids'][] = $dashboardid;
				}
			}
		}

		if (!$criteria) {
			return;
		}

		$where_or = [];

		foreach ($criteria as $templateid => $criterion) {
			$where_and = [
				dbConditionId('templateid', [$templateid]),
				dbConditionString('name', $criterion['names'])
			];

			if ($criterion['dashboardids']) {
				$where_and[] = dbConditionId('dashboardid', $criterion['dashboardids'], true);
			}

			$where_or[] = implode(' AND ', $where_and);
		}

		$where = (count($where_or) == 1) ? $where_or[0] : '('.implode(') OR (', $where_or).')';

		$duplicate = DBfetch(DBselect('SELECT name FROM dashboard WHERE '.$where));

		if ($duplicate) {
			self::exception(ZBX_API_ERROR_PARAMETERS,
				_s('Dashboard "%1$s" already exists.', $duplicate['name'])
			);
		}
	}

	/**
	 * Check widgets.
	 *
	 * Note: For any object with ID in $dashboards a corresponding object in $db_dashboards must exist.
	 *
	 * @param array      $dashboards
	 * @param array|null $db_dashboards
	 *
	 * @throws APIException if the input is invalid.
	 */
	protected function checkWidgets(array $dashboards, array $db_dashboards = null): void {
		$widget_defaults = DB::getDefaults('widget');

		foreach ($dashboards as $dashboard) {
			if (!array_key_exists('pages', $dashboard)) {
				continue;
			}

			$db_pages = ($db_dashboards !== null) ? $db_dashboards[$dashboard['dashboardid']]['pages'] : null;

			foreach ($dashboard['pages'] as $page_index => $page) {
				if (!array_key_exists('widgets', $page)) {
					continue;
				}

				$filled = [];

				foreach ($page['widgets'] as $widget) {
					$widget += array_key_exists('widgetid', $widget)
						? $db_pages[$page['dashboard_pageid']]['widgets'][$widget['widgetid']]
						: $widget_defaults;

					for ($x = $widget['x']; $x < $widget['x'] + $widget['width']; $x++) {
						for ($y = $widget['y']; $y < $widget['y'] + $widget['height']; $y++) {
							if (array_key_exists($x, $filled) && array_key_exists($y, $filled[$x])) {
								self::exception(ZBX_API_ERROR_PARAMETERS,
									_s('Overlapping widgets at X:%3$d, Y:%4$d on page #%2$d of dashboard "%1$s".',
										$dashboard['name'], $page_index, $widget['x'], $widget['y']
									)
								);
							}

							$filled[$x][$y] = true;
						}
					}

					if ($widget['x'] + $widget['width'] > DASHBOARD_MAX_COLUMNS
							|| $widget['y'] + $widget['height'] > DASHBOARD_MAX_ROWS) {
						self::exception(ZBX_API_ERROR_PARAMETERS,
							_s('Widget at X:%3$d, Y:%4$d on page #%2$d of dashboard "%1$s" is out of bounds.',
								$dashboard['name'], $page_index, $widget['x'], $widget['y']
							)
						);
					}
				}
			}
		}
	}

	/**
	 * Check widget fields.
	 *
	 * Note: For any object with ID in $dashboards a corresponding object in $db_dashboards must exist.
	 *
	 * @param array      $dashboards
	 * @param array|null $db_dashboards
	 *
	 * @throws APIException if the input is invalid.
	 */
	protected function checkWidgetFields(array $dashboards, array $db_dashboards = null): void {
		$ids = [
			ZBX_WIDGET_FIELD_TYPE_ITEM => [],
			ZBX_WIDGET_FIELD_TYPE_ITEM_PROTOTYPE => [],
			ZBX_WIDGET_FIELD_TYPE_GRAPH => [],
			ZBX_WIDGET_FIELD_TYPE_GRAPH_PROTOTYPE => [],
			ZBX_WIDGET_FIELD_TYPE_GROUP => [],
			ZBX_WIDGET_FIELD_TYPE_HOST => [],
			ZBX_WIDGET_FIELD_TYPE_MAP => []
		];

		foreach ($dashboards as $dashboard) {
			if (!array_key_exists('pages', $dashboard)) {
				continue;
			}

			$db_pages = ($db_dashboards !== null) ? $db_dashboards[$dashboard['dashboardid']]['pages'] : null;

			foreach ($dashboard['pages'] as $page) {
				if (!array_key_exists('widgets', $page)) {
					continue;
				}

				foreach ($page['widgets'] as $widget) {
					if (!array_key_exists('fields', $widget)) {
						continue;
					}

					$widgetid = array_key_exists('widgetid', $widget) ? $widget['widgetid'] : null;

					// Skip testing linked object availability of already stored fields.
					$current_fields = [];

					if ($widgetid !== null) {
						$db_widget = $db_pages[$page['dashboard_pageid']]['widgets'][$widgetid];

						foreach ($db_widget['fields'] as $db_field) {
							$current_fields[$db_field['type']][$db_field['value']] = true;
						}
					}

					foreach ($widget['fields'] as $field) {
						if ($widgetid === null
								|| !array_key_exists($field['type'], $current_fields)
								|| !array_key_exists($field['value'], $current_fields[$field['type']])) {
							if ($this instanceof CTemplateDashboard) {
								$ids[$field['type']][$field['value']][$dashboard['templateid']] = true;
							}
							else {
								$ids[$field['type']][$field['value']] = true;
							}
						}
					}
				}
			}
		}

		if ($ids[ZBX_WIDGET_FIELD_TYPE_ITEM]) {
			$itemids = array_keys($ids[ZBX_WIDGET_FIELD_TYPE_ITEM]);

			$db_items = API::Item()->get([
				'output' => ($this instanceof CTemplateDashboard) ? ['hostid'] : [],
				'itemids' => $itemids,
				'webitems' => true,
				'preservekeys' => true
			]);

			foreach ($itemids as $itemid) {
				if (!array_key_exists($itemid, $db_items)) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _s('Item with ID "%1$s" is not available.', $itemid));
				}

				if ($this instanceof CTemplateDashboard) {
					foreach (array_keys($ids[ZBX_WIDGET_FIELD_TYPE_ITEM][$itemid]) as $templateid) {
						if ($db_items[$itemid]['hostid'] != $templateid) {
							self::exception(ZBX_API_ERROR_PARAMETERS,
								_s('Item with ID "%1$s" is not available.', $itemid)
							);
						}
					}
				}
			}
		}

		if ($ids[ZBX_WIDGET_FIELD_TYPE_ITEM_PROTOTYPE]) {
			$item_prototypeids = array_keys($ids[ZBX_WIDGET_FIELD_TYPE_ITEM_PROTOTYPE]);

			$db_item_prototypes = API::ItemPrototype()->get([
				'output' => ($this instanceof CTemplateDashboard) ? ['hostid'] : [],
				'itemids' => $item_prototypeids,
				'preservekeys' => true
			]);

			foreach ($item_prototypeids as $item_prototypeid) {
				if (!array_key_exists($item_prototypeid, $db_item_prototypes)) {
					self::exception(ZBX_API_ERROR_PARAMETERS,
						_s('Item prototype with ID "%1$s" is not available.', $item_prototypeid)
					);
				}

				if ($this instanceof CTemplateDashboard) {
					foreach (array_keys($ids[ZBX_WIDGET_FIELD_TYPE_ITEM_PROTOTYPE][$item_prototypeid]) as $templateid) {
						if ($db_item_prototypes[$item_prototypeid]['hostid'] != $templateid) {
							self::exception(ZBX_API_ERROR_PARAMETERS,
								_s('Item prototype with ID "%1$s" is not available.', $item_prototypeid)
							);
						}
					}
				}
			}
		}

		if ($ids[ZBX_WIDGET_FIELD_TYPE_GRAPH]) {
			$graphids = array_keys($ids[ZBX_WIDGET_FIELD_TYPE_GRAPH]);

			$db_graphs = API::Graph()->get([
				'output' => [],
				'selectHosts' => ($this instanceof CTemplateDashboard) ? ['hostid'] : null,
				'graphids' => $graphids,
				'preservekeys' => true
			]);

			foreach ($graphids as $graphid) {
				if (!array_key_exists($graphid, $db_graphs)) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _s('Graph with ID "%1$s" is not available.', $graphid));
				}

				if ($this instanceof CTemplateDashboard) {
					foreach (array_keys($ids[ZBX_WIDGET_FIELD_TYPE_GRAPH][$graphid]) as $templateid) {
						if (!in_array($templateid, array_column($db_graphs[$graphid]['hosts'], 'hostid'))) {
							self::exception(ZBX_API_ERROR_PARAMETERS,
								_s('Graph with ID "%1$s" is not available.', $graphid)
							);
						}
					}
				}
			}
		}

		if ($ids[ZBX_WIDGET_FIELD_TYPE_GRAPH_PROTOTYPE]) {
			$graph_prototypeids = array_keys($ids[ZBX_WIDGET_FIELD_TYPE_GRAPH_PROTOTYPE]);

			$db_graph_prototypes = API::GraphPrototype()->get([
				'output' => [],
				'selectHosts' => ($this instanceof CTemplateDashboard) ? ['hostid'] : null,
				'graphids' => $graph_prototypeids,
				'preservekeys' => true
			]);

			foreach ($graph_prototypeids as $graph_prototypeid) {
				if (!array_key_exists($graph_prototypeid, $db_graph_prototypes)) {
					self::exception(ZBX_API_ERROR_PARAMETERS,
						_s('Graph prototype with ID "%1$s" is not available.', $graph_prototypeid)
					);
				}

				if ($this instanceof CTemplateDashboard) {
					$templateids = array_keys($ids[ZBX_WIDGET_FIELD_TYPE_GRAPH_PROTOTYPE][$graph_prototypeid]);
					foreach ($templateids as $templateid) {
						$hostids = array_column($db_graph_prototypes[$graph_prototypeid]['hosts'], 'hostid');
						if (!in_array($templateid, $hostids)) {
							self::exception(ZBX_API_ERROR_PARAMETERS,
								_s('Graph prototype with ID "%1$s" is not available.', $graph_prototypeid)
							);
						}
					}
				}
			}
		}

		if ($ids[ZBX_WIDGET_FIELD_TYPE_GROUP]) {
			$groupids = array_keys($ids[ZBX_WIDGET_FIELD_TYPE_GROUP]);

			$db_groups = API::HostGroup()->get([
				'output' => [],
				'groupids' => $groupids,
				'preservekeys' => true
			]);

			foreach ($groupids as $groupid) {
				if (!array_key_exists($groupid, $db_groups)) {
					self::exception(ZBX_API_ERROR_PARAMETERS,
						_s('Host group with ID "%1$s" is not available.', $groupid)
					);
				}
			}
		}

		if ($ids[ZBX_WIDGET_FIELD_TYPE_HOST]) {
			$hostids = array_keys($ids[ZBX_WIDGET_FIELD_TYPE_HOST]);

			$db_hosts = API::Host()->get([
				'output' => [],
				'hostids' => $hostids,
				'preservekeys' => true
			]);

			foreach ($hostids as $hostid) {
				if (!array_key_exists($hostid, $db_hosts)) {
					self::exception(ZBX_API_ERROR_PARAMETERS, _s('Host with ID "%1$s" is not available.', $hostid));
				}
			}
		}

		if ($ids[ZBX_WIDGET_FIELD_TYPE_MAP]) {
			$sysmapids = array_keys($ids[ZBX_WIDGET_FIELD_TYPE_MAP]);

			$db_sysmaps = API::Map()->get([
				'output' => [],
				'sysmapids' => $sysmapids,
				'preservekeys' => true
			]);

			foreach ($sysmapids as $sysmapid) {
				if (!array_key_exists($sysmapid, $db_sysmaps)) {
					self::exception(ZBX_API_ERROR_PARAMETERS,
						_s('Map with ID "%1$s" is not available.', $sysmapid)
					);
				}
			}
		}
	}

	/**
	 * Update table "dashboard_page".
	 *
	 * @param array      $dashboards
	 * @param array|null $db_dashboards
	 */
	protected function updatePages(array $dashboards, array $db_dashboards = null): void {
		$db_pages = [];

		if ($db_dashboards !== null) {
			foreach ($dashboards as $dashboard) {
				if (array_key_exists('pages', $dashboard)) {
					$db_pages += $db_dashboards[$dashboard['dashboardid']]['pages'];
				}
			}
		}

		$ins_pages = [];
		$upd_pages = [];

		foreach ($dashboards as $dashboard) {
			if (!array_key_exists('pages', $dashboard)) {
				continue;
			}

			foreach ($dashboard['pages'] as $page_index => $page) {
				$page['sortorder'] = $page_index;

				if (array_key_exists('dashboard_pageid', $page)) {
					$upd_page = dbUpdatedValues('dashboard_page', $page, $db_pages[$page['dashboard_pageid']]);

					if ($upd_page) {
						$upd_pages[] = [
							'values' => $upd_page,
							'where' => ['dashboard_pageid' => $page['dashboard_pageid']]
						];
					}

					unset($db_pages[$page['dashboard_pageid']]);
				}
				else {
					unset($page['widgets']);

					$ins_pages[] = ['dashboardid' => $dashboard['dashboardid']] + $page;
				}
			}
		}

		if ($ins_pages) {
			$pageids = DB::insert('dashboard_page', $ins_pages);

			foreach ($dashboards as &$dashboard) {
				if (array_key_exists('pages', $dashboard)) {
					foreach ($dashboard['pages'] as &$page) {
						if (!array_key_exists('dashboard_pageid', $page)) {
							$page['dashboard_pageid'] = array_shift($pageids);
						}
					}
					unset($page);
				}
			}
			unset($dashboard);
		}

		if ($upd_pages) {
			DB::update('dashboard_page', $upd_pages);
		}

		$this->updateWidgets($dashboards, $db_dashboards);

		if ($db_pages) {
			DB::delete('dashboard_page', ['dashboard_pageid' => array_keys($db_pages)]);
		}
	}

	/**
	 * Update table "widget".
	 *
	 * @param array      $dashboards
	 * @param array|null $db_dashboards
	 */
	protected function updateWidgets(array $dashboards, array $db_dashboards = null): void {
		$db_widgets = [];

		if ($db_dashboards !== null) {
			foreach ($dashboards as $dashboard) {
				if (!array_key_exists('pages', $dashboard)) {
					continue;
				}

				$db_pages = $db_dashboards[$dashboard['dashboardid']]['pages'];

				foreach ($dashboard['pages'] as $page) {
					if (!array_key_exists('widgets', $page)) {
						continue;
					}

					if (array_key_exists($page['dashboard_pageid'], $db_pages)) {
						$db_widgets += $db_pages[$page['dashboard_pageid']]['widgets'];
					}
				}
			}
		}

		$ins_widgets = [];
		$upd_widgets = [];

		foreach ($dashboards as $dashboard) {
			if (!array_key_exists('pages', $dashboard)) {
				continue;
			}

			foreach ($dashboard['pages'] as $page) {
				if (!array_key_exists('widgets', $page)) {
					continue;
				}

				foreach ($page['widgets'] as $widget) {
					if (array_key_exists('widgetid', $widget)) {
						$upd_widget = dbUpdatedValues('widget', $widget, $db_widgets[$widget['widgetid']]);

						if ($upd_widget) {
							$upd_widgets[] = [
								'values' => $upd_widget,
								'where' => ['widgetid' => $widget['widgetid']]
							];
						}

						unset($db_widgets[$widget['widgetid']]);
					}
					else {
						$ins_widgets[] = ['dashboard_pageid' => $page['dashboard_pageid']] + $widget;
					}
				}
			}
		}

		if ($ins_widgets) {
			$widgetids = DB::insert('widget', $ins_widgets);

			foreach ($dashboards as &$dashboard) {
				if (array_key_exists('pages', $dashboard)) {
					foreach ($dashboard['pages'] as &$page) {
						if (array_key_exists('widgets', $page)) {
							foreach ($page['widgets'] as &$widget) {
								if (!array_key_exists('widgetid', $widget)) {
									$widget['widgetid'] = array_shift($widgetids);
								}
							}
							unset($widget);
						}
					}
					unset($page);
				}
			}
			unset($dashboard);
		}

		if ($upd_widgets) {
			DB::update('widget', $upd_widgets);
		}

		if ($db_widgets) {
			self::deleteWidgets(array_keys($db_widgets));
		}

		$this->updateWidgetFields($dashboards, $db_dashboards);
	}

	/**
	 * Update table "widget_field".
	 *
	 * @param array      $dashboards
	 * @param array|null $db_dashboards
	 */
	protected function updateWidgetFields(array $dashboards, array $db_dashboards = null): void {
		$ins_fields = [];
		$upd_fields = [];
		$del_fieldids = [];

		foreach ($dashboards as $dashboard) {
			if (!array_key_exists('pages', $dashboard)) {
				continue;
			}

			$db_pages = ($db_dashboards !== null) ? $db_dashboards[$dashboard['dashboardid']]['pages'] : [];

			foreach ($dashboard['pages'] as $page) {
				if (!array_key_exists('widgets', $page)) {
					continue;
				}

				$db_widgets = array_key_exists($page['dashboard_pageid'], $db_pages)
					? $db_pages[$page['dashboard_pageid']]['widgets']
					: [];

				foreach ($page['widgets'] as $widget) {
					if (!array_key_exists('fields', $widget)) {
						continue;
					}

					$db_fields = array_key_exists($widget['widgetid'], $db_widgets)
						? $db_widgets[$widget['widgetid']]['fields']
						: [];

					$fields = [];

					foreach ($widget['fields'] as $field) {
						$field[self::WIDGET_FIELD_TYPE_COLUMNS[$field['type']]] = $field['value'];
						$fields[$field['type']][$field['name']][] = $field;
					}

					foreach ($db_fields as $db_field) {
						if (array_key_exists($db_field['type'], $fields)
								&& array_key_exists($db_field['name'], $fields[$db_field['type']])
								&& $fields[$db_field['type']][$db_field['name']]) {
							$field = array_shift($fields[$db_field['type']][$db_field['name']]);

							$upd_field = dbUpdatedValues('widget_field', $field, $db_field);

							if ($upd_field) {
								$upd_fields[] = [
									'values' => $upd_field,
									'where' => ['widget_fieldid' => $db_field['widget_fieldid']]
								];
							}
						}
						else {
							$del_fieldids[] = $db_field['widget_fieldid'];
						}
					}

					foreach ($fields as $fields) {
						foreach ($fields as $fields) {
							foreach ($fields as $field) {
								$ins_fields[] = ['widgetid' => $widget['widgetid']] + $field;
							}
						}
					}
				}
			}
		}

		if ($ins_fields) {
			DB::insert('widget_field', $ins_fields);
		}

		if ($upd_fields) {
			DB::update('widget_field', $upd_fields);
		}

		if ($del_fieldids) {
			DB::delete('widget_field', ['widget_fieldid' => $del_fieldids]);
		}
	}

	/**
	 * Delete widgets.
	 *
	 * @static
	 *
	 * @param array $widgetids
	 */
	protected static function deleteWidgets(array $widgetids): void {
		DB::delete('profiles', [
			'idx' => 'web.dashbrd.widget.rf_rate',
			'idx2' => $widgetids
		]);

		DB::delete('widget', ['widgetid' => $widgetids]);
	}

	protected function addRelatedObjects(array $options, array $result) {
		$result = parent::addRelatedObjects($options, $result);

		$dashboardids = array_keys($result);

		// Adding user shares.
		if ($options['selectUsers'] !== null) {
			$relation_map = $this->createRelationMap($result, 'dashboardid', 'userid', 'dashboard_user');
			// Get all allowed users.
			$db_users = API::User()->get([
				'output' => [],
				'userids' => $relation_map->getRelatedIds(),
				'preservekeys' => true
			]);

			if ($db_users) {
				$db_dashboard_users = API::getApiService()->select('dashboard_user', [
					'output' => $this->outputExtend($options['selectUsers'], ['dashboardid', 'userid']),
					'filter' => ['dashboardid' => $dashboardids, 'userid' => array_keys($db_users)],
					'preservekeys' => true
				]);

				$relation_map = $this->createRelationMap($db_dashboard_users, 'dashboardid', 'dashboard_userid');

				$db_dashboard_users = $this->unsetExtraFields($db_dashboard_users, ['userid'], $options['selectUsers']);

				foreach ($db_dashboard_users as &$db_dashboard_user) {
					unset($db_dashboard_user['dashboard_userid'], $db_dashboard_user['dashboardid']);
				}
				unset($db_dashboard_user);

				$result = $relation_map->mapMany($result, $db_dashboard_users, 'users');
			}
			else {
				foreach ($result as &$row) {
					$row['users'] = [];
				}
				unset($row);
			}
		}

		// Adding user group shares.
		if ($options['selectUserGroups'] !== null) {
			$relation_map = $this->createRelationMap($result, 'dashboardid', 'usrgrpid', 'dashboard_usrgrp');
			// Get all allowed groups.
			$db_usrgrps = API::UserGroup()->get([
				'output' => [],
				'usrgrpids' => $relation_map->getRelatedIds(),
				'preservekeys' => true
			]);

			if ($db_usrgrps) {
				$db_dashboard_usrgrps = API::getApiService()->select('dashboard_usrgrp', [
					'output' => $this->outputExtend($options['selectUserGroups'], ['dashboardid', 'usrgrpid']),
					'filter' => ['dashboardid' => $dashboardids, 'usrgrpid' => array_keys($db_usrgrps)],
					'preservekeys' => true
				]);

				$relation_map = $this->createRelationMap($db_dashboard_usrgrps, 'dashboardid', 'dashboard_usrgrpid');

				$db_dashboard_usrgrps =
					$this->unsetExtraFields($db_dashboard_usrgrps, ['usrgrpid'], $options['selectUserGroups']);

				foreach ($db_dashboard_usrgrps as &$db_dashboard_usrgrp) {
					unset($db_dashboard_usrgrp['dashboard_usrgrpid'], $db_dashboard_usrgrp['dashboardid']);
				}
				unset($db_dashboard_usrgrp);

				$result = $relation_map->mapMany($result, $db_dashboard_usrgrps, 'userGroups');
			}
			else {
				foreach ($result as &$row) {
					$row['userGroups'] = [];
				}
				unset($row);
			}
		}

		// Adding dashboard pages.
		if ($options['selectPages'] !== null) {
			foreach ($result as &$row) {
				$row['pages'] = [];
			}
			unset($row);

			$widgets_requested = $this->outputIsRequested('widgets', $options['selectPages']);

			if ($widgets_requested && is_array($options['selectPages'])) {
				$options['selectPages'] = array_diff($options['selectPages'], ['widgets']);
			}

			$db_pages = API::getApiService()->select('dashboard_page', [
				'output' => $this->outputExtend($options['selectPages'], ['dashboardid', 'sortorder']),
				'filter' => ['dashboardid' => $dashboardids],
				'preservekeys' => true
			]);

			uasort($db_pages,
				function(array $db_page_1, array $db_page_2): int {
					return $db_page_1['sortorder'] <=> $db_page_2['sortorder'];
				}
			);

			if ($widgets_requested) {
				foreach ($db_pages as &$db_page) {
					$db_page['widgets'] = [];
				}
				unset($db_page);

				$db_widgets = API::getApiService()->select('widget', [
					'output' => API_OUTPUT_EXTEND,
					'filter' => ['dashboard_pageid' => array_keys($db_pages)],
					'preservekeys' => true
				]);

				if ($db_widgets) {
					foreach ($db_widgets as &$db_widget) {
						$db_widget['fields'] = [];
					}
					unset($db_widget);

					$db_fields = API::getApiService()->select('widget_field', [
						'output' => API_OUTPUT_EXTEND,
						'filter' => [
							'widgetid' => array_keys($db_widgets),
							'type' => array_keys(self::WIDGET_FIELD_TYPE_COLUMNS)
						]
					]);

					foreach ($db_fields as $db_field) {
						$db_widgets[$db_field['widgetid']]['fields'][] = [
							'type' => $db_field['type'],
							'name' => $db_field['name'],
							'value' => $db_field[self::WIDGET_FIELD_TYPE_COLUMNS[$db_field['type']]]
						];
					}
				}

				foreach ($db_widgets as $db_widget) {
					$db_pages[$db_widget['dashboard_pageid']]['widgets'][] = array_diff_key($db_widget, array_flip([
						'dashboard_pageid'
					]));
				}
			}

			$db_pages = $this->unsetExtraFields($db_pages, ['dashboard_pageid'], $options['selectPages']);

			foreach ($db_pages as $db_page) {
				$result[$db_page['dashboardid']]['pages'][] = array_diff_key($db_page, array_flip(['dashboardid',
					'sortorder'
				]));
			}
		}

		return $result;
	}
}
