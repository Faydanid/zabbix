<?php
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

require_once dirname(__FILE__).'/../../include/CWebTest.php';
require_once dirname(__FILE__).'/../traits/TableTrait.php';
require_once dirname(__FILE__).'/../behaviors/CMessageBehavior.php';
require_once dirname(__FILE__).'/../../include/helpers/CDataHelper.php';

use Facebook\WebDriver\WebDriverKeys;

/**
 * @backup role, module, users, report, services
 * @dataSource ExecuteNowAction
 * @onBefore prepareUserData, prepareReportData, prepareServiceData
 */
class testUserRolesPermissions extends CWebTest {

	use TableTrait;

	/**
	 * Attach MessageBehavior to the test.
	 */
	public function getBehaviors() {
		return [CMessageBehavior::class];
	}

	/**
	 * Id of role that created for future role change for Super admin.
	 *
	 * @var integer
	 */
	protected static $super_roleid;

	/**
	 * Id of user that created for future checks.
	 *
	 * @var integer
	 */
	protected static $super_user;

	/**
	 * Id of created scheduled report.
	 *
	 * @var integer
	 */
	protected static $reportid;

	/**
	 * Function used to create user.
	 */
	public function prepareUserData() {
		$role = CDataHelper::call('role.create', [
			[
				'name' => 'super_role',
				'type' => 3,
				'rules' => [
					'services.write.mode' => 1
				]
			]
		]);
		$this->assertArrayHasKey('roleids', $role);
		self::$super_roleid = $role['roleids'][0];

		$user = CDataHelper::call('user.create', [
			[
				'username' => 'user_for_role',
				'passwd' => 'zabbixzabbix',
				'roleid' => self::$super_roleid,
				'usrgrps' => [
					[
						'usrgrpid' => '7'
					]
				]
			]
		]);
		$this->assertArrayHasKey('userids', $user);
		self::$super_user = $user['userids'][0];
	}

	/**
	 * Scheduled report.
	 */
	public function prepareReportData() {
		$response = CDataHelper::call('report.create', [
			[
				'userid' => self::$super_user,
				'name' => 'test_report_for_role',
				'dashboardid' => '1',
				'users' => [
					[
						'userid' => self::$super_user,
						'exclude' => '0'
					]
				]
			]
		]);
		$this->assertArrayHasKey('reportids', $response);
		self::$reportid = $response['reportids'][0];
	}

	public function prepareServiceData() {
		CDataHelper::call('service.create', [
			[
				'name' => 'Parent 1',
				'algorithm' => 1,
				'sortorder' => 1
			],
			[
				'name' => 'Parent 2',
				'algorithm' => 2,
				'sortorder' => 2
			],
			[
				'name' => 'Child of parent 1',
				'algorithm' => 2,
				'sortorder' => 1,
				'tags' => [
					[
						'tag' => 'test',
						'value' => 'test123'
					]
				]
			],
			[
				'name' => 'Child of child 1',
				'algorithm' => 2,
				'sortorder' => 1
			],
			[
				'name' => 'Child of parent 2',
				'algorithm' => 2,
				'sortorder' => 1
			]
		]);

		$services = CDataHelper::getIds('name');

		CDataHelper::call('service.update', [
			[
				'serviceid' =>  $services['Child of parent 1'],
				'parents' => [
					[
						'serviceid' => $services['Parent 1']
					]
				],
				'children' => [
					[
						'serviceid' => $services['Child of child 1']
					]
				]
			],
			[
				'serviceid' => $services['Child of parent 2'],
				'parents' => [
					[
						'serviceid' => $services['Parent 2']
					]
				]
			]
		]);
	}

	public static function getPageActionsData() {
		return [
			// Map creation/edit.
			[
				[
					'page_buttons' => [
						'Create map',
						'Import',
						'Delete'
					],
					'form_button' => [
						'Edit map'
					],
					'list_link' => 'sysmaps.php',
					'action_link' => 'zabbix.php?action=map.view&sysmapid=1',
					'action' => 'Create and edit maps',
					'check_links' => ['sysmap.php?sysmapid=1', 'sysmaps.php?form=Create+map']
				]
			],
			// Dashboard creation/edit.
			[
				[
					'page_buttons' => [
						'Create dashboard',
						'Delete'
					],
					'form_button' => [
						'Edit dashboard'
					],
					'list_link' => 'zabbix.php?action=dashboard.list',
					'action_link' => 'zabbix.php?action=dashboard.view&dashboardid=1220',
					'action' => 'Create and edit dashboards',
					'check_links' => ['zabbix.php?action=dashboard.view&new=1']
				]
			],
			// Maintenance creation/edit.
			[
				[
					'maintenance' => true,
					'page_buttons' => [
						'Create maintenance period',
						'Delete'
					],
					'form_button' => [
						'Update',
						'Clone',
						'Delete',
						'Cancel'
					],
					'list_link' => 'maintenance.php',
					'action_link' => 'maintenance.php?form=update&maintenanceid=5',
					'action' => 'Create and edit maintenance',
					'check_links' => ['maintenance.php?form=create']
				]
			],
			// Manage scheduled reports.
			[
				[
					'report' => true,
					'page_buttons' => [
						'Create report',
						'Enable',
						'Disable',
						'Delete'
					],
					'form_button' => [
						'Update',
						'Clone',
						'Test',
						'Delete',
						'Cancel'
					],
					'list_link' => 'zabbix.php?action=scheduledreport.list',
					'action' => 'Manage scheduled reports',
					'check_links' => ['zabbix.php?action=scheduledreport.edit']
				]
			]
		];
	}

	/**
	 * Check creation/edit for dashboard, map, reports, maintenance.
	 *
	 * @dataProvider getPageActionsData
	 */
	public function testUserRolesPermissions_PageActions($data) {
		$this->page->userLogin('user_for_role', 'zabbixzabbix');

		foreach ([true, false] as $action_status) {
			$this->page->open($data['list_link'])->waitUntilReady();

			$this->query('class:list-table')->asTable()->waitUntilVisible()->one()->getRow(0)->select();
			foreach ($data['page_buttons'] as $button) {
				$this->assertTrue($this->query('button', $button)->one()->isEnabled($action_status));
			}

			$this->page->open(array_key_exists('report', $data) ? 'zabbix.php?action=scheduledreport.edit&reportid='.
					self::$reportid : $data['action_link'])->waitUntilReady();

			foreach ($data['form_button'] as $text) {
				$this->assertTrue($this->query('button', $text)->one()->isEnabled(($text === 'Cancel') ? true : $action_status));
			}

			if ($action_status) {
				$this->changeRoleRule([$data['action'] => false]);
			}
		}

		$this->checkLinks($data['check_links']);
	}

	public static function getProblemActionsData() {
		return [
			// Message.
			[
				[
					'activityid' => 'message',
					'action' => 'Add problem comments',
					'column' => 'Message',
					'value' => 'test_text'
				]
			],
			// Severity.
			[
				[
					'activityid' => 'change_severity',
					'action' => 'Change severity',
					'column' => 'Severity',
					'value' => 'Average'
				]
			],
			// Close problem.
			[
				[
					'activityid' => 'close_problem',
					'action' => 'Close problems',
					'column' => 'Status',
					'value' => 'CLOSING'
				]
			],
			// Acknowledge problem.
			[
				[
					'activityid' => 'acknowledge_problem',
					'action' => 'Acknowledge problems',
					'column' => 'Ack',
					'value' => 'Yes'
				]
			]
		];
	}

	/**
	 * Check problem actions.
	 *
	 * @backupOnce events
	 *
	 * @dataProvider getProblemActionsData
	 */
	public function testUserRolesPermissions_ProblemAction($data) {
		$this->page->userLogin('user_for_role', 'zabbixzabbix');

		foreach ([true, false] as $action_status) {
			$this->page->open('zabbix.php?action=problem.view')->waitUntilReady();
			$row = $this->query('class:list-table')->asTable()->one()->findRow('Problem', 'Test trigger with tag');
			$row->getColumn('Ack')->query('link:No')->waitUntilClickable()->one()->click();
			$dialog = COverlayDialogElement::find()->waitUntilReady()->one();
			$this->assertTrue($dialog->query('id', $data['activityid'])->one()->isEnabled($action_status));
			$this->changeRoleRule([$data['action'] => !$action_status]);

			// Check that problem actions works after they were turned on.
			if ($action_status === false) {
				$this->page->open('zabbix.php?action=problem.view')->waitUntilReady();
				$row->getColumn('Ack')->query('link:No')->waitUntilCLickable()->one()->click();
				COverlayDialogElement::find()->waitUntilReady()->one();

				if ($data['activityid'] === 'message') {
					$dialog->query('id:message')->one()->fill('test_text');
					$dialog->query('button:Update')->one()->click();
					$dialog->ensureNotPresent();
					$this->page->waitUntilReady();
					$row->getColumn('Actions')->query('xpath:.//button[contains(@class, "icon-action-msgs")]')->one()->click();
					$message_hint = $this->query('xpath://div[@data-hintboxid]')->asOverlayDialog()->waitUntilPresent()->all()->last();
					$value = $message_hint->query('class:list-table')->asTable()->one()->getRow(0)->getColumn($data['column'])->getText();
					$this->assertEquals($data['value'], $value);
				}
				else {
					$dialog->query('id', $data['activityid'])->asCheckbox()->one()->check();

					if ($data['activityid'] === 'change_severity') {
						$dialog->query('id:severity')->asSegmentedRadio()->one()->fill('Average');
					}

					$dialog->query('button:Update')->one()->click();
					$this->page->waitUntilReady();
					$status = $row->getColumn($data['column'])->getText();
					$this->assertEquals($data['value'], $status);
				}
			}
		}
	}

	/**
	 * Check that Acknowledge link is disabled after all problem actions is disabled.
	 */
	public function testUserRolesPermissions_ProblemsActionsAll() {
		$problem = 'Test trigger with tag';
		$actions = [
			'Add problem comments' => false,
			'Change severity' => false,
			'Acknowledge problems' => false,
			'Suppress problems' => false,
			'Close problems' => false
		];
		$this->page->userLogin('user_for_role', 'zabbixzabbix');

		foreach ([true, false] as $action_status) {
			// Problem page.
			$this->page->open('zabbix.php?action=problem.view')->waitUntilReady();
			$problem_row = $this->query('class:list-table')->asTable()->one()->findRow('Problem', $problem);
			$this->assertEquals($action_status, $problem_row->getColumn('Ack')->query('xpath:.//*[text()="No"]')
					->one()->isAttributePresent('onclick'));

			// Problem widget in dashboard.
			$this->page->open('zabbix.php?action=dashboard.view&dashboardid=1')->waitUntilReady();
			$table = CDashboardElement::find()->one()->getWidget('Current problems')->query('class:list-table')->asTable()->one();
			$this->assertEquals($action_status, $table->findRow('Problem • Severity', $problem)->getColumn('Ack')
					->query('xpath:.//*[text()="No"]')->one()->isAttributePresent('onclick'));

			// Event details page.
			$this->page->open('tr_events.php?triggerid=99251&eventid=93')->waitUntilReady();

			foreach (['Event details', 'Event list [previous 20]'] as $table_name) {
				$table = $this->query('xpath://h4[text()='.CXPathHelper::escapeQuotes($table_name).']/../..//table')->asTable()->one();
				$this->assertEquals($action_status, $table->query('xpath:.//*[text()="No"]')
						->one()->isAttributePresent('onclick'));
			}

			if ($action_status) {
				$this->changeRoleRule($actions);
			}
		}
	}

	public static function getScriptActionData() {
		return [
			// Monitoring problems page.
			[
				[
					'link' => 'zabbix.php?action=problem.view',
					'selector' => 'xpath:(//a[@class="link-action wordbreak" and text()="ЗАББИКС Сервер"])[1]'
				]
			],
			// Dashboard problem widget.
			[
				[
					'link' => 'zabbix.php?action=dashboard.view&dashboardid=1',
					'selector' => 'link:ЗАББИКС Сервер'
				]
			],
			// Monitoring hosts page.
			[
				[
					'link' => 'zabbix.php?action=host.view',
					'selector' => 'link:3_Host_to_check_Monitoring_Overview'
				]
			],
			// Event detail page.
			[
				[
					'link' => 'tr_events.php?triggerid=99251&eventid=93',
					'selector' => 'xpath:(//*[@class="list-table"])[1]//*[text()="ЗАББИКС Сервер"]'
				]
			],
			// Monitoring maps page.
			[
				[
					'link' => 'zabbix.php?action=map.view&sysmapid=1',
					'selector' => 'xpath://*[name()="g"][@class="map-elements"]/*[name()="image"]'
				]
			]
		];
	}

	/**
	 * Check script actions.
	 *
	 * @dataProvider getScriptActionData
	 */
	public function testUserRolesPermissions_ScriptAction($data) {
		$context_before = [
			'Inventory',
			'Latest data',
			'Problems',
			'Graphs',
			'Dashboards',
			'Web',
			'Configuration',
			'Detect operating system',
			'Ping',
			'Script for Clone',
			'Script for Delete',
			'Script for Update',
			'Traceroute'
		];
		$context_after = [
			'Inventory',
			'Latest data',
			'Problems',
			'Graphs',
			'Dashboards',
			'Web',
			'Configuration'
		];
		$this->page->userLogin('user_for_role', 'zabbixzabbix');

		foreach ([true, false] as $action_status) {
			$this->page->open($data['link'])->waitUntilReady();
			$this->query($data['selector'])->waitUntilPresent()->one()->click();

			$popup = CPopupMenuElement::find()->waitUntilVisible()->one();
			if ($action_status) {
				$this->assertTrue($popup->hasItems($context_before));
				$this->assertEquals(['HOST', 'SCRIPTS'], $popup->getTitles()->asText());
				$this->changeRoleRule(['Execute scripts' => false]);
			}
			else {
				$this->assertTrue($popup->hasItems($context_after));
				$this->assertEquals(['HOST'], $popup->getTitles()->asText());
				$this->changeRoleRule(['Execute scripts' => true]);
			}
		}
	}

	/**
	 * Module enable/disable.
	 */
	public function testUserRolesPermissions_Module() {
		$pages_before = [
			'Dashboards',
			'Monitoring',
			'Services',
			'Inventory',
			'Reports',
			'Data collection',
			'Alerts',
			'Users',
			'Administration',
			'Module 5 menu'
		];
		$this->page->userLogin('user_for_role', 'zabbixzabbix');
		$this->page->open('zabbix.php?action=module.list')->waitUntilReady();
		$this->query('button:Scan directory')->one()->click();
		$this->query('class:list-table')->asTable()->one()->findRows('Name', '5th Module')->select();
		$this->query('button:Enable')->one()->click();
		$this->page->acceptAlert();
		$this->page->waitUntilReady();

		foreach ([true, false] as $action_status) {
			$page_number = $this->query('xpath://ul[@class="menu-main"]/li/a')->count();
			$all_pages = [];

			for ($i = 1; $i <= $page_number; ++$i) {
				$all_pages[] = $this->query('xpath:(//ul[@class="menu-main"]/li/a)['.$i.']')->one()->getText();
			}

			if ($action_status) {
				$this->assertEquals($pages_before, $all_pages);
				$this->changeRoleRule(['5th Module' => false]);
			}
			else {
				$pages_after = array_values(array_diff($pages_before, ['Module 5 menu']));
				$this->assertEquals($pages_after, $all_pages);
			}
		}
	}

	public static function getUIData() {
		return [
			[
				[
					'section' => 'Inventory',
					'page' => 'Overview',
					'displayed_ui' => [
						'Hosts'
					],
					'link' => ['hostinventoriesoverview.php']
				]
			],
			[
				[
					'section' => 'Inventory',
					'page' => 'Hosts',
					'displayed_ui' => [
						'Overview'
					],
					'link' => ['hostinventories.php']
				]
			],
			[
				[
					'section' => 'Reports',
					'page' => 'Availability report',
					'displayed_ui' => [
						'Scheduled reports',
						'System information',
						'Triggers top 100',
						'Audit log',
						'Action log',
						'Notifications'
					],
					'link' => ['report2.php']
				]
			],
			[
				[
					'section' => 'Reports',
					'page' => 'System information',
					'displayed_ui' => [
						'Scheduled reports',
						'Availability report',
						'Triggers top 100',
						'Audit log',
						'Action log',
						'Notifications'
					],
					'link' => ['zabbix.php?action=report.status']
				]
			],
			[
				[
					'section' => 'Reports',
					'page' => 'Availability report',
					'displayed_ui' => [
						'System information',
						'Scheduled reports',
						'Triggers top 100',
						'Audit log',
						'Action log',
						'Notifications'
					],
					'link' => ['report2.php']
				]
			],
			[
				[
					'section' => 'Reports',
					'page' => 'Triggers top 100',
					'displayed_ui' => [
						'Availability report',
						'System information',
						'Scheduled reports',
						'Audit log',
						'Action log',
						'Notifications'
					],
					'link' => ['toptriggers.php']
				]
			],
			[
				[
					'section' => 'Reports',
					'page' => 'Audit log',
					'displayed_ui' => [
						'Availability report',
						'System information',
						'Scheduled reports',
						'Triggers top 100',
						'Action log',
						'Notifications'
					],
					'link' => ['zabbix.php?action=auditlog.list']
				]
			],
			[
				[
					'section' => 'Reports',
					'page' => 'Action log',
					'displayed_ui' => [
						'Availability report',
						'System information',
						'Scheduled reports',
						'Triggers top 100',
						'Audit log',
						'Notifications'
					],
					'link' => ['auditacts.php']
				]
			],
			[
				[
					'section' => 'Reports',
					'page' => 'Notifications',
					'displayed_ui' => [
						'Availability report',
						'System information',
						'Scheduled reports',
						'Triggers top 100',
						'Audit log',
						'Action log'
					],
					'link' => ['report4.php']
				]
			],
			[
				[
					'section' => 'Data collection',
					'page' => 'Template groups',
					'displayed_ui' => [
						'Host groups',
						'Templates',
						'Hosts',
						'Maintenance',
						'Event correlation',
						'Discovery'
					],
					'link' => ['zabbix.php?action=templategroup.list']
				]
			],
			[
				[
					'section' => 'Data collection',
					'page' => 'Host groups',
					'displayed_ui' => [
						'Template groups',
						'Templates',
						'Hosts',
						'Maintenance',
						'Event correlation',
						'Discovery'
					],
					'link' => ['zabbix.php?action=hostgroup.list']
				]
			],
			[
				[
					'section' => 'Data collection',
					'page' => 'Templates',
					'displayed_ui' => [
						'Template groups',
						'Host groups',
						'Hosts',
						'Maintenance',
						'Event correlation',
						'Discovery'
					],
					'link' => ['templates.php']
				]
			],
			[
				[
					'section' => 'Data collection',
					'page' => 'Hosts',
					'displayed_ui' => [
						'Template groups',
						'Host groups',
						'Templates',
						'Maintenance',
						'Event correlation',
						'Discovery'
					],
					'link' => ['zabbix.php?action=host.list']
				]
			],
			[
				[
					'section' => 'Data collection',
					'page' => 'Maintenance',
					'displayed_ui' => [
						'Template groups',
						'Host groups',
						'Templates',
						'Hosts',
						'Event correlation',
						'Discovery'
					],
					'link' => ['maintenance.php']
				]
			],
			[
				[
					'section' => 'Data collection',
					'page' => 'Event correlation',
					'displayed_ui' => [
						'Template groups',
						'Host groups',
						'Templates',
						'Hosts',
						'Maintenance',
						'Discovery'
					],
					'link' => ['zabbix.php?action=correlation.list']
				]
			],
			[
				[
					'section' => 'Data collection',
					'page' => 'Discovery',
					'displayed_ui' => [
						'Template groups',
						'Host groups',
						'Templates',
						'Hosts',
						'Maintenance',
						'Event correlation'
					],
					'link' => ['zabbix.php?action=discovery.list']
				]
			],
			[
				[
					'section' => 'Alerts',
					'page' => 'Actions',
					'displayed_ui' => [
						'Media types',
						'Scripts'
					],
					'link' => [
						'actionconf.php?eventsource=0',
						'actionconf.php?eventsource=1',
						'actionconf.php?eventsource=2',
						'actionconf.php?eventsource=3',
						'actionconf.php?eventsource=4'
					]
				]
			],
			[
				[
					'section' => 'Alerts',
					'page' => 'Media types',
					'displayed_ui' => [
						'Trigger actions',
						'Service actions',
						'Discovery actions',
						'Autoregistration actions',
						'Internal actions',
						'Scripts'
					],
					'link' => ['zabbix.php?action=mediatype.list']
				]
			],
			[
				[
					'section' => 'Alerts',
					'page' => 'Scripts',
					'displayed_ui' => [
						'Trigger actions',
						'Service actions',
						'Discovery actions',
						'Autoregistration actions',
						'Internal actions',
						'Media types'
					],
					'link' => ['zabbix.php?action=script.list']
				]
			],
			[
				[
					'section' => 'Users',
					'page' => 'User groups',
					'displayed_ui' => [
						'User roles',
						'Users',
						'API tokens',
						'Authentication'
					],
					'link' => ['zabbix.php?action=usergroup.list']
				]
			],
			[
				[
					'section' => 'Users',
					'user_roles' => true,
					'page' => 'User roles',
					'displayed_ui' => [
						'User groups',
						'Users',
						'API tokens',
						'Authentication'
					],
					'link' => ['zabbix.php?action=userrole.list']
				]
			],
			[
				[
					'section' => 'Users',
					'page' => 'Users',
					'displayed_ui' => [
						'User groups',
						'User roles',
						'API tokens',
						'Authentication'
					],
					'link' => ['zabbix.php?action=user.list']
				]
			],
			[
				[
					'section' => 'Users',
					'page' => 'API tokens',
					'displayed_ui' => [
						'User groups',
						'User roles',
						'Users',
						'Authentication'
					],
					'link' => ['zabbix.php?action=token.list']
				]
			],
			[
				[
					'section' => 'Users',
					'page' => 'Authentication',
					'displayed_ui' => [
						'User groups',
						'User roles',
						'Users',
						'API tokens'
					],
					'link' => ['zabbix.php?action=authentication.edit']
				]
			],
			[
				[
					'section' => 'Administration',
					'page' => 'General',
					'displayed_ui' => [
						'Audit log',
						'Housekeeping',
						'Proxies',
						'Macros',
						'Queue'
					],
					'link' => [
						'zabbix.php?action=gui.edit',
						'zabbix.php?action=autoreg.edit',
						'zabbix.php?action=image.list',
						'zabbix.php?action=iconmap.list',
						'zabbix.php?action=regex.list',
						'zabbix.php?action=trigdisplay.edit',
						'zabbix.php?action=geomaps.edit',
						'zabbix.php?action=module.list',
						'zabbix.php?action=miscconfig.edit'
					]
				]
			],
			[
				[
					'section' => 'Administration',
					'page' => 'Audit log',
					'displayed_ui' => [
						'General',
						'Proxies',
						'Housekeeping',
						'Macros',
						'Queue'
					],
					'link' => ['zabbix.php?action=audit.settings.edit']
				]
			],
			[
				[
					'section' => 'Administration',
					'page' => 'Housekeeping',
					'displayed_ui' => [
						'General',
						'Audit log',
						'Proxies',
						'Macros',
						'Queue'
					],
					'link' => ['zabbix.php?action=housekeeping.edit']
				]
			],
			[
				[
					'section' => 'Administration',
					'page' => 'Proxies',
					'displayed_ui' => [
						'General',
						'Audit log',
						'Housekeeping',
						'Macros',
						'Queue'
					],
					'link' => ['zabbix.php?action=proxy.list']
				]
			],
			[
				[
					'section' => 'Administration',
					'page' => 'Macros',
					'displayed_ui' => [
						'General',
						'Audit log',
						'Housekeeping',
						'Proxies',
						'Queue'
					],
					'link' => ['zabbix.php?action=macros.edit']
				]
			],
			[
				[
					'section' => 'Administration',
					'page' => 'Queue',
					'displayed_ui' => [
						'General',
						'Audit log',
						'Housekeeping',
						'Proxies',
						'Macros'
					],
					'link' => [
						'zabbix.php?action=queue.overview',
						'zabbix.php?action=queue.overview.proxy',
						'zabbix.php?action=queue.details'
					]
				]
			],
			[
				[
					'section' => 'Monitoring',
					'page' => 'Problems',
					'displayed_ui' => [
						'Hosts',
						'Latest data',
						'Maps',
						'Discovery'
					],
					'link' => ['zabbix.php?action=problem.view']
				]
			],
			[
				[
					'section' => 'Monitoring',
					'page' => 'Hosts',
					'displayed_ui' => [
						'Problems',
						'Latest data',
						'Maps',
						'Discovery'
					],
					'link' => ['zabbix.php?action=host.view']
				]
			],
			[
				[
					'section' => 'Monitoring',
					'page' => 'Latest data',
					'displayed_ui' => [
						'Problems',
						'Hosts',
						'Maps',
						'Discovery'
					],
					'link' => ['zabbix.php?action=latest.view']
				]
			],
			[
				[
					'section' => 'Monitoring',
					'page' => 'Maps',
					'displayed_ui' => [
						'Problems',
						'Hosts',
						'Latest data',
						'Discovery'
					],
					'link' => ['sysmaps.php']
				]
			],
			[
				[
					'section' => 'Monitoring',
					'page' => 'Discovery',
					'displayed_ui' => [
						'Problems',
						'Hosts',
						'Latest data',
						'Maps'
					],
					'link' => ['zabbix.php?action=discovery.view']
				]
			],
			[
				[
					'section' => 'Services',
					'page' => 'Services',
					'displayed_ui' => [
						'SLA',
						'SLA report'
					],
					'link' => ['zabbix.php?action=service.list']
				]
			],
			[
				[
					'section' => 'Services',
					'page' => 'SLA',
					'displayed_ui' => [
						'Services',
						'SLA report'
					],
					'link' => ['zabbix.php?action=sla.list']
				]
			],
			[
				[
					'section' => 'Services',
					'page' => 'SLA report',
					'displayed_ui' => [
						'Services',
						'SLA'
					],
					'link' => ['zabbix.php?action=slareport.list']
				]
			]
		];
	}

	/**
	 * UI permission
	 *
	 * @dataProvider getUIData
	 */
	public function testUserRolesPermissions_UI($data) {
		$user_roles = [
			'Users' => [
				'User groups',
				'User roles',
				'Users',
				'API tokens',
				'Authentication'
			]
		];
		$this->page->userLogin('user_for_role', 'zabbixzabbix');

		foreach ([true, false] as $action_status) {
			$menu = CMainMenuElement::find()->one();

			if ($data['section'] !== 'Dashboards') {
				$menu->select($data['section']);
			}

			if ($data['page'] === $data['section']) {
				$submenu = $menu->query("xpath:.//a[text()=".CXPathHelper::escapeQuotes($data['section']).
						"]/../ul[@class='submenu']")->one();
				$this->assertEquals($action_status, $submenu->query('link', $data['page'])->one(false)->isValid());
			}
			else {
				$this->assertEquals($action_status, $menu->exists($data['page']));
			}

			if ($action_status) {
				if (array_key_exists('user_roles', $data)) {
					$this->signOut();
					$this->page->userLogin('Admin', 'zabbix');
					$this->changeRoleRule([$data['section'] => $data['displayed_ui']]);
					$this->signOut();
					$this->page->userLogin('user_for_role', 'zabbixzabbix');
				}
				else {
					$this->changeRoleRule([$data['section'] => $data['displayed_ui']]);
					$this->page->open('zabbix.php?action=dashboard.view')->waitUntilReady();
				}
			}
			else {
				if (array_key_exists('user_roles', $data)) {
					$this->checkLinks($data['link']);
					$this->signOut();
					$this->page->userLogin('Admin', 'zabbix');
					$this->changeRoleRule($user_roles);
					$this->signOut();
				}
				else {
					$this->checkLinks($data['link']);
					$this->signOut();
				}
			}
		}
	}

	/**
	 * Manage API token action check.
	 */
	public function testUserRolesPermissions_ManageApiToken() {
		$this->page->userLogin('user_for_role', 'zabbixzabbix');
		$this->page->open('zabbix.php?action=user.token.list')->waitUntilReady();
		$this->assertEquals('TEST_SERVER_NAME: API tokens', $this->page->getTitle());
		$this->changeRoleRule(['Manage API tokens' => false]);
		$this->checkLinks(['zabbix.php?action=user.token.list']);
	}

	/**
	 * Disabling access to Dashboard. Check warning message text and button.
	 */
	public function testUserRolesPermissions_Dashboard() {
		$this->page->userLogin('user_for_role', 'zabbixzabbix');
		$this->page->open('zabbix.php?action=dashboard.view')->waitUntilReady();
		$this->changeRoleRule(['Dashboards' => false]);
		$this->checkLinks(['zabbix.php?action=dashboard.view'], 'Problems');
	}

	public static function getRoleServiceData() {
		return [
			[
				[
					'role_config' => [
						'Read-write access to services' => 'None',
						'Read-only access to services' => 'None'
					],
					'services' => null
				]
			],
			[
				[
					'role_config' => [
						'Read-write access to services' => 'All',
						'Read-only access to services' => 'None'
					],
					'services' => [
						'Child of child 1' => 'write',
						'Child of parent 1' => 'write',
						'Child of parent 2' => 'write',
						'Parent 1' => 'write',
						'Parent 2' => 'write'
					]
				]
			],
			[
				[
					'role_config' => [
						'Read-write access to services' => 'None',
						'Read-only access to services' => 'All'
					],
					'services' => [
						'Child of child 1' => 'read',
						'Child of parent 1' => 'read',
						'Child of parent 2' => 'read',
						'Parent 1' => 'read',
						'Parent 2' => 'read'
					]
				]
			],
			[
				[
					'role_config' => [
						'Read-write access to services' => 'All',
						'Read-only access to services' => 'All'
					],
					'services' => [
						'Child of child 1' => 'write',
						'Child of parent 1' => 'write',
						'Child of parent 2' => 'write',
						'Parent 1' => 'write',
						'Parent 2' => 'write'
					]
				]
			],
			[
				[
					'role_config' => [
						'Read-write access to services' => 'Service list',
						'Read-only access to services' => 'None'
					],
					'service_list' => [
						'Read-write access to services with tag' => [
							'service-write-tag-tag' => 'test',
							'service_write_tag_value' => 'test123'
						]
					],
					'services' => [
						'Child of child 1' => 'write',
						'Child of parent 1' => 'write'
					]
				]
			],
			[
				[
					'role_config' => [
						'Read-write access to services' => 'None',
						'Read-only access to services' => 'Service list'
					],
					'service_list' => [
						'Read-only access to services with tag' => [
							'service-read-tag-tag' => 'test',
							'service_read_tag_value' => 'test123'
						]
					],
					'services' => [
						'Child of child 1' => 'read',
						'Child of parent 1' => 'read'
					]
				]
			],
			[
				[
					'role_config' => [
						'Read-write access to services' => 'Service list',
						'Read-only access to services' => 'None'
					],
					'service_list' => [
						'xpath:(//div[@class="multiselect-control"])[1]' => 'Child of parent 1'
					],
					'services' => [
						'Child of child 1' => 'write',
						'Child of parent 1' => 'write'
					]
				]
			],
			[
				[
					'role_config' => [
						'Read-write access to services' => 'None',
						'Read-only access to services' => 'Service list',
						// added element 'API methods' with default value for page scroll
						'API methods' => 'Deny list'
					],
					'service_list' => [
						'xpath:(//div[@class="multiselect-control"])[2]' => 'Child of parent 1'
					],
					'services' => [
						'Child of child 1' => 'read',
						'Child of parent 1' => 'read'
					]
				]
			],
			[
				[
					'role_config' => [
						'Read-write access to services' => 'Service list',
						'Read-only access to services' => 'All'
					],
					'service_list' => [
						'xpath:(//div[@class="multiselect-control"])[1]' => 'Child of parent 1'
					],
					'services' => [
						'Child of child 1' => 'write',
						'Child of parent 1' => 'write',
						'Child of parent 2' => 'read',
						'Parent 1' => 'read',
						'Parent 2' => 'read'
					]
				]
			]
		];
	}

	/**
	 * Check permissions to services based on user role configuration.
	 *
	 * @dataProvider getRoleServiceData
	 */
	public function testUserRolesPermissions_ServicePermissions($data) {
		// Prepare a combination of service name and the number of child services for service for further comparison.
		if ($data['services'] !== null) {
			$child_services = [
				'Child of parent 1' => 1,
				'Parent 1' => 1,
				'Parent 2' => 1
			];
			$column_content = [];

			foreach (array_keys($data['services']) as $service) {
				$column_content[] = array_key_exists($service, $child_services)
					? $service.' '.$child_services[$service]
					: $service;
			}
		}

		// Configure the role according to the data provider.
		$this->page->login()->open('zabbix.php?action=userrole.edit&roleid='.self::$super_roleid)->waitUntilReady();
		$form = $this->query('id:userrole-form')->waitUntilPresent()->asForm()->one();
		$form->fill($data['role_config']);

		if (array_key_exists('service_list', $data)) {
			$form->fill($data['service_list']);
		}
		$form->submit();
		$this->page->logout();

		// Login as user that belongs to the updated row and check access to services based on applied configuration.
		$this->page->userLogin('user_for_role', 'zabbixzabbix');
		$this->page->open('zabbix.php?action=service.list')->waitUntilReady();

		$services_mode = $this->query('id:list_mode')->asSegmentedRadio()->one(false);

		// Check that table service list content and edit mode in not available if the user doest have permissions.
		if ($data['services'] === null) {
			$this->assertTableData();
			$this->assertFalse($services_mode->isValid());

			return;
		}
		elseif ($data['role_config']['Read-write access to services'] !== 'None') {
			// Open edit mode if user has write permissions to at least one service.
			$services_mode->select('Edit');
			$this->page->waituntilReady();
		}

		// Filter out unnecessary services.
		$this->query('id:filter_tags_0_tag')->waitUntilVisible()->one()->fill('action');
		$this->query('id:filter_tags_0_operator')->asDropdown()->waitUntilVisible()->one()->fill('Does not exist');

		// Apply filter in order to see the list of available services.
		$this->query('name:filter_set')->waitUntilClickable()->one()->click();
		$this->page->waituntilReady();

		$this->assertTableDataColumn($column_content, 'Name');
		$table = $this->query('class:list-table')->asTable()->one();

		// Check buttons are not visible for user with no permissions, otherwise, check edit permissions per service.
		if ($data['role_config']['Read-write access to services'] === 'None') {
			foreach ($table->getRows() as $row) {
				$this->assertEquals(0, $row->query('xpath:.//button')->all(false)->count());
			}
		}
		else {
			foreach ($data['services'] as $service => $permissions) {
				$property = ($permissions === 'write') ? CElementFilter::CLICKABLE : CElementFilter::NOT_CLICKABLE;
				$row = $table->findRow('Name', $service, true);
				// Check that all three action buttons in the row are clickable.
				$this->assertEquals(3, $row->query("xpath:.//button")->all()
						->filter(new CElementFilter($property))->count()
				);
			}
		}
	}

	public static function getExecuteNowButtonData() {
		return [
			[
				[
					'user' => 'U1-r-on',
					'test_cases' => [
						// Simple items.
						[
							'items' => ['I4-trap-log']
						],
						[
							'expected' => TEST_GOOD,
							'items' => ['I5-agent-txt', 'I4-trap-log'],
							'message' => 'Request sent successfully. Some items are filtered due to access permissions or type.'
						],
						// Dependet items.
						[
							'expected' => TEST_GOOD,
							'items' => ['I1-lvl2-dep-log'],
							'message' => 'Request sent successfully'
						],
						[
							'expected' => TEST_BAD,
							'items' => ['I2-lvl2-dep-log'],
							'message' => 'Cannot send request: wrong master item type.'
						]
					]
				]
			],
			[
				[
					'user' => 'U2-r-off',
					'test_cases' => [
						// Simple items.
						[
							'items' => ['I4-trap-log']
						],
						[
							'items' => ['I5-agent-txt', 'I4-trap-log']
						],
						// Dependet items.
						[
							'items' => ['I1-lvl2-dep-log']
						],
						[
							'items' => ['I2-lvl2-dep-log']
						]
					]
				]
			],
			[
				[
					'user' => 'U3-rw-off',
					'test_cases' => [
						// Simple items.
						[
							'items' => ['I4-trap-log']
						],
						// Dependet items.
						[
							'expected' => TEST_GOOD,
							'items' => ['I1-lvl2-dep-log', 'I4-trap-log'],
							'message' => 'Request sent successfully. Some items are filtered due to access permissions or type.'
						],
						[
							'expected' => TEST_BAD,
							'items' => ['I2-lvl2-dep-log'],
							'message' => 'Cannot send request: wrong master item type.'
						]
					]
				]
			]
		];
	}

	/**
	 * Check permissions to "Execute now" button on Latest data page based on user role.
	 *
	 * @dataProvider getExecuteNowButtonData
	 */
	public function testUserRolesPermissions_ExecuteNowButton($data) {
		// Login and select host group for testing.
		$this->page->userLogin($data['user'], 'zabbixzabbix');
		$this->page->open('zabbix.php?action=latest.view')->waitUntilReady();
		$table = $this->query('xpath://table['.CXPathHelper::fromClass('overflow-ellipsis').']')->asTable()->one();
		$filter_form = $this->query('name:zbx_filter')->asForm()->one();
		$filter_form->fill(['Host groups' => 'HG-for-executenow']);
		$filter_form->submit();
		$table->waitUntilReloaded();

		$selected_count = $this->query('id:selected_count')->one();
		$select_all = $this->query('id:all_items')->asCheckbox()->one();

		foreach ($data['test_cases'] as $test_case) {
			$table->findRows('Name', $test_case['items'])->select();
			$this->assertEquals(count($test_case['items']).' selected', $selected_count->getText());

			// Disabled "Execute now" button.
			if (!array_key_exists('expected', $test_case)) {
				$this->assertTrue($this->query('button:Execute now')->one()->isEnabled(false));
				// Reset selected items.
				$select_all->check();
				$select_all->uncheck();
				$this->assertEquals('0 selected', $selected_count->getText());
				continue;
			}

			$this->query('button:Execute now')->one()->click();

			switch (CTestArrayHelper::get($test_case, 'expected')) {
				case TEST_GOOD:
					$this->assertMessage(TEST_GOOD, $test_case['message']);
					// After a successful "Execute now" action, the item selection is reset.
					$this->assertEquals('0 selected', $selected_count->getText());
					break;

				case TEST_BAD:
					$this->assertMessage(TEST_BAD, 'Cannot execute operation', $test_case['message']);
					// Reset selected items after a failed "Execute now" action.
					$this->assertEquals(count($test_case['items']).' selected', $selected_count->getText());
					$select_all->check();
					$select_all->uncheck();
					$this->assertEquals('0 selected', $selected_count->getText());
					break;
			}

			CMessageElement::find()->waitUntilVisible()->one()->close();
		}
	}

	public static function getExecuteNowContextMenuData() {
		return [
			[
				[
					'user' => 'U1-r-on',
					'test_cases' => [
						[
							'items' => ['I4-trap-log', 'I2-lvl1-trap-num']
						],
						[
							'expected' => TEST_GOOD,
							'items' => 'I1-lvl2-dep-log'
						],
						[
							'expected' => TEST_BAD,
							'items' => 'I2-lvl2-dep-log',
							'message' => 'Cannot send request: wrong master item type.'
						]
					]
				]
			],
			[
				[
					'user' => 'U2-r-off',
					'test_cases' => [
						[
							'items' => ['I4-trap-log', 'I5-agent-txt', 'I1-lvl2-dep-log', 'I2-lvl2-dep-log']
						]
					]
				]
			],
			[
				[
					'user' => 'U3-rw-off',
					'test_cases' => [
						[
							'items' => ['I4-trap-log', 'I2-lvl1-trap-num']
						],
						[
							'expected' => TEST_GOOD,
							'items' => 'I1-lvl2-dep-log'
						],
						[
							'expected' => TEST_BAD,
							'items' => 'I2-lvl2-dep-log',
							'message' => 'Cannot send request: wrong master item type.'
						]
					]
				]
			]
		];
	}

	/**
	 * Check permissions to "Execute now" link in context menu on Latest data page based on user role.
	 *
	 * @dataProvider getExecuteNowContextMenuData
	 */
	public function testUserRolesPermissions_ExecuteNowContextMenu($data) {
		// Login and select host group for testing.
		$this->page->userLogin($data['user'], 'zabbixzabbix');
		$this->page->open('zabbix.php?action=latest.view')->waitUntilReady();
		$filter_form = $this->query('name:zbx_filter')->asForm()->one();
		$filter_form->fill(['Host groups' => 'HG-for-executenow']);
		$filter_form->submit();
		$this->page->waitUntilReady();

		foreach ($data['test_cases'] as $test_case) {
			// Disabled "Execute now" option in context menu.
			if (!array_key_exists('expected', $test_case)) {
				foreach ($test_case['items'] as $item) {
					$this->query('link', $item)->one()->click();
					$popup = CPopupMenuElement::find()->waitUntilVisible()->one();
					$this->assertFalse($popup->getItem('Execute now')->isEnabled());
					$this->page->pressKey(WebDriverKeys::ESCAPE);
				}

				continue;
			}

			$this->query('link', $test_case['items'])->one()->click();
			$popup = CPopupMenuElement::find()->waitUntilVisible()->one();
			$popup->fill('Execute now');

			if ($test_case['expected'] === TEST_GOOD) {
				$this->assertMessage(TEST_GOOD, 'Request sent successfully');
			}
			else {
				$this->assertMessage(TEST_BAD, 'Cannot execute operation', $test_case['message']);
			}

			CMessageElement::find()->waitUntilVisible()->one()->close();
		}
	}

	/**
	 * Check disabled actions with links.
	 *
	 * @param array $links		checked links after disabling action
	 * @param string $page		page name displayed on error message button
	 */
	private function checkLinks($links, $page = 'Dashboards') {
		foreach ($links as $link) {
			$this->page->open($link)->waitUntilReady();
			$this->assertMessage(TEST_BAD, 'Access denied', 'You are logged in as "user_for_role". '.
					'You have no permissions to access this page.');
			$this->query('button:Go to "'.$page.'"')->one()->waitUntilClickable()->click();

			if ($page === 'Dashboards') {
				$this->assertStringContainsString('zabbix.php?action=dashboard', $this->page->getCurrentUrl());
			}
		}
	}

	/**
	 * Enable/disable actions and UI.
	 *
	 * @param array $action		action with true/false status or UI section with page
	 */
	private function changeRoleRule($action) {
		$this->page->open('zabbix.php?action=userrole.edit&roleid='.self::$super_roleid)->waitUntilReady();
		$this->query('id:userrole-form')->waitUntilPresent()->asForm()->one()->fill($action)->submit();
		$this->page->waitUntilReady();
		$this->assertMessage(TEST_GOOD, 'User role updated');
	}

	/**
	 * Click Sign out button.
	 */
	private function signOut() {
		$this->query('xpath://a[@class="icon-signout"]')->waitUntilPresent()->one()->click();
		$this->page->waitUntilReady();
		$this->query('button:Sign in')->waitUntilVisible();
	}
}
