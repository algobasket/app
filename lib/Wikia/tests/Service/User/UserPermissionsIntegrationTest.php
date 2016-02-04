<?php

namespace Wikia\Service\User\Permissions;

use Wikia\DependencyInjection\Injector;
use PHPUnit_Framework_TestCase;

class UserPermissionsIntegrationTest extends \WikiaBaseTest {

	/**
	 * @var int
	 */
	protected $testUserId;

	/**
	 * @var int
	 */
	protected $testCityId;

	/**
	 * @var PermissionsService
	 */
	protected $permissionsService;

	/**
	 * @var \User
	 */
	protected $staffUser;

	/**
	 * @var \User
	 */
	protected $anonUser;

	/**
	 * @var string
	 */
	const TEST_WIKI_NAME = "muppet";

	/**
	 * @var string
	 */
	const TEST_USER_NAME = "JCel";

	protected function setUp() {
		$this->testUserName = self::TEST_USER_NAME;
		$this->testUserId = \User::idFromName( $this->testUserName );
		$this->testCityId = \WikiFactory::DBtoID( self::TEST_WIKI_NAME );
		$this->staffUser = \User::newFromId( $this->testUserId );
		$this->anonUser = \User::newFromId( 0 );
		$this->permissionsService = Injector::getInjector()->get( PermissionsService::class );

		parent::setUp();
	}

	function testShouldReturnStaffExplicitGroup() {
		\WikiaDataAccess::cachePurge( PermissionsServiceImpl::getMemcKey( $this->testUserId ) );

		$groups = $this->permissionsService->getExplicitUserGroups( $this->testCityId, $this->testUserId );
		$this->assertContains("staff", $groups);
	}

	function testShouldReturnAutomaticGroups() {
		$groups = $this->permissionsService->getAutomaticUserGroups( $this->staffUser, true );
		$this->assertContains("user", $groups);
		$this->assertContains("autoconfirmed", $groups);
		$this->assertContains("*", $groups);
	}

	function testShouldReturnEffectiveGroups() {
		$groups = $this->permissionsService->getEffectiveUserGroups( $this->testCityId, $this->staffUser, true );
		$this->assertContains("user", $groups);
		$this->assertContains("staff", $groups);
		$this->assertContains("autoconfirmed", $groups);
		$this->assertContains("*", $groups);
	}

	function testShouldReturnEffectiveGroupsForAnon() {
		$groups = $this->permissionsService->getEffectiveUserGroups( $this->testCityId, $this->anonUser, true );
		$this->assertContains("*", $groups);
		$this->assertEquals(1, count($groups));
	}

	function testShouldReturnImplicitGroups() {
		$groups = $this->permissionsService->getImplicitGroups();
		$this->assertContains("*", $groups);
		$this->assertContains("user", $groups);
		$this->assertContains("autoconfirmed", $groups);
		$this->assertContains("poweruser", $groups);
	}

	function testShouldReturnGroupPermissions() {
		global $wgGroupPermissions, $wgRevokePermissions;

		# Data for regular $wgGroupPermissions test
		$wgGroupPermissions['unittesters'] = array(
			'test' => true,
			'runtest' => true,
			'writetest' => false,
			'nukeworld' => false,
		);
		$wgGroupPermissions['testwriters'] = array(
			'test' => true,
			'writetest' => true,
			'modifytest' => true,
		);
		# Data for regular $wgRevokePermissions test
		$wgRevokePermissions['formertesters'] = array(
			'runtest' => true,
		);

		$permissions = $this->permissionsService->getGroupPermissions( array( 'unittesters' ) );
		$this->assertContains( 'runtest', $permissions );
		$this->assertNotContains( 'writetest', $permissions );
		$this->assertNotContains( 'modifytest', $permissions );
		$this->assertNotContains( 'nukeworld', $permissions );

		$permissions = $this->permissionsService->getGroupPermissions( array( 'unittesters', 'testwriters' ) );
		$this->assertContains( 'runtest', $permissions );
		$this->assertContains( 'writetest', $permissions );
		$this->assertContains( 'modifytest', $permissions );
		$this->assertNotContains( 'nukeworld', $permissions );
	}

	public function testShouldReturnGroupPermissionsIncludingRevoked() {
		global $wgGroupPermissions, $wgRevokePermissions;

		# Data for regular $wgGroupPermissions test
		$wgGroupPermissions['unittesters'] = array(
			'test' => true,
			'runtest' => true,
			'writetest' => false,
			'nukeworld' => false,
		);
		$wgGroupPermissions['testwriters'] = array(
			'test' => true,
			'writetest' => true,
			'modifytest' => true,
		);
		# Data for regular $wgRevokePermissions test
		$wgRevokePermissions['formertesters'] = array(
			'runtest' => true,
		);

		$permissions = $this->permissionsService->getGroupPermissions( array( 'unittesters', 'formertesters' ) );
		$this->assertNotContains( 'runtest', $permissions );
		$this->assertNotContains( 'writetest', $permissions );
		$this->assertNotContains( 'modifytest', $permissions );
		$this->assertNotContains( 'nukeworld', $permissions );
	}

	function testShouldReturnGroupsWithPermission() {
		global $wgGroupPermissions, $wgRevokePermissions;

		# Data for regular $wgGroupPermissions test
		$wgGroupPermissions['unittesters'] = array(
			'test' => true,
			'runtest' => true,
			'writetest' => false,
			'nukeworld' => false,
		);
		$wgGroupPermissions['testwriters'] = array(
			'test' => true,
			'writetest' => true,
			'modifytest' => true,
		);
		# Data for regular $wgRevokePermissions test
		$wgRevokePermissions['formertesters'] = array(
			'runtest' => true,
		);

		$groups = $this->permissionsService->getGroupsWithPermission( 'test' );
		$this->assertContains( 'unittesters', $groups );
		$this->assertContains( 'testwriters', $groups );
		$this->assertEquals(2, count($groups));

		$groups = $this->permissionsService->getGroupsWithPermission( 'runtest' );
		$this->assertContains( 'unittesters', $groups );
		$this->assertEquals(1, count($groups));

		$groups = $this->permissionsService->getGroupsWithPermission( 'nosuchright' );
		$this->assertEquals(0, count($groups));
	}

	public function testShouldReturnUserPermissions() {
		$permissions = $this->permissionsService->getUserPermissions( $this->testCityId, $this->staffUser );
		$this->assertContains( 'setadminskin', $permissions );
		$this->assertContains( 'delete', $permissions );
		$this->assertContains( 'block', $permissions );
	}

	public function testShouldReturnPermissionsNotDuplicated() {
		$permissions = $this->permissionsService->getPermissions();
		$this->assertContains( 'move', $permissions );
		$this->assertContains( 'oversight', $permissions );

		$permissionCount = [];
		foreach ( $permissions as $permission ) {
			if ( array_key_exists( $permission, $permissionCount ) ) {
				$permissionCount[ $permission ] = $permissionCount[ $permission ] + 1;
			} else {
				$permissionCount[ $permission ] = 1;
			}
		}
		foreach ( $permissionCount as $permissionName => $permissionCount ) {
			$this->assertEquals( 1, $permissionCount, "Duplicated permission ".$permissionName );
		}
	}

	public function testShouldReturnGroupsChangeableByGroups() {
		$groups = $this->permissionsService->getGroupsChangeableByGroup( 'util' );
		$this->assertContains( 'util', $groups['add'] );
		$this->assertContains( 'util', $groups['remove'] );
		$this->assertNotContains( 'util', $groups['add-self'] );
		$this->assertNotContains( 'util', $groups['remove-self'] );
		$this->assertNotContains( 'user', $groups['add'] );
		$this->assertNotContains( 'user', $groups['remove'] );
		$this->assertNotContains( 'user', $groups['add-self'] );
		$this->assertNotContains( 'user', $groups['remove-self'] );
		$this->assertContains( 'translator', $groups['add'] );
		$this->assertContains( 'translator', $groups['remove'] );
		$this->assertNotContains( 'translator', $groups['add-self'] );
		$this->assertNotContains( 'translator', $groups['remove-self'] );

		$groups = $this->permissionsService->getGroupsChangeableByGroup( 'content-reviewer' );
		$this->assertNotContains( 'staff', $groups['add'] );
		$this->assertNotContains( 'staff', $groups['remove'] );
		$this->assertContains( 'content-reviewer', $groups['add'] );
		$this->assertContains( 'content-reviewer', $groups['remove'] );
		$this->assertNotContains( 'content-reviewer', $groups['add-self'] );
		$this->assertNotContains( 'content-reviewer', $groups['remove-self'] );

		$groups = $this->permissionsService->getGroupsChangeableByGroup( 'staff' );
		$this->assertContains( 'staff', $groups['add'] );
		$this->assertContains( 'staff', $groups['remove'] );
		$this->assertContains( 'staff', $groups['add-self'] );
		$this->assertContains( 'staff', $groups['remove-self'] );
	}

	public function testShouldAddAndRemoveGlobalGroup() {
		$this->mockGlobalVariable('wgWikiaIsCentralWiki', true);

		$groups = $this->permissionsService->getExplicitGlobalUserGroups( $this->testUserId );
		if ( !in_array( 'reviewer', $groups ) ) {
			$this->permissionsService->addUserToGroup( $this->testCityId, $this->staffUser, $this->staffUser, 'reviewer' );
		}
		$groups = $this->permissionsService->getExplicitGlobalUserGroups( $this->testUserId );
		$this->assertContains( 'reviewer', $groups );

		$this->permissionsService->removeUserFromGroup( $this->testCityId, $this->staffUser, $this->staffUser, 'reviewer' );
		$groups = $this->permissionsService->getExplicitGlobalUserGroups( $this->testUserId );
		$this->assertNotContains( 'reviewer', $groups );

		$this->permissionsService->addUserToGroup( $this->testCityId, $this->staffUser, $this->staffUser, 'reviewer' );
		$groups = $this->permissionsService->getExplicitGlobalUserGroups( $this->testUserId );
		$this->assertContains( 'reviewer', $groups );

		$groups = $this->permissionsService->getExplicitGlobalUserGroups( $this->testUserId );
		$this->assertNotContains( 'content-review', $groups );
		$this->assertFalse( $this->permissionsService->addUserToGroup(
			$this->testCityId, $this->staffUser, $this->staffUser, 'content-review' ) );
		$groups = $this->permissionsService->getExplicitGlobalUserGroups( $this->testUserId );
		$this->assertNotContains( 'content-review', $groups );

		$this->assertFalse( $this->permissionsService->removeUserFromGroup(
			$this->testCityId, $this->staffUser, $this->staffUser, 'some-made-up-group' ) );
	}

	public function testShouldAddAndRemoveLocalGroup() {
		$groups = $this->permissionsService->getExplicitLocalUserGroups( $this->testCityId, $this->testUserId );
		if ( !in_array( 'bureaucrat', $groups ) ) {
			$this->permissionsService->addUserToGroup( $this->testCityId, $this->staffUser, $this->staffUser, 'bureaucrat' );
		}
		$groups = $this->permissionsService->getExplicitLocalUserGroups( $this->testCityId, $this->testUserId );
		$this->assertContains( 'bureaucrat', $groups );

		$this->permissionsService->removeUserFromGroup( $this->testCityId, $this->staffUser, $this->staffUser, 'bureaucrat' );
		$groups = $this->permissionsService->getExplicitLocalUserGroups( $this->testCityId, $this->testUserId );
		$this->assertNotContains( 'bureaucrat', $groups );

		$this->permissionsService->addUserToGroup( $this->testCityId, $this->staffUser, $this->staffUser, 'bureaucrat' );
		$groups = $this->permissionsService->getExplicitLocalUserGroups( $this->testCityId, $this->testUserId );
		$this->assertContains( 'bureaucrat', $groups );
	}

	public function testShouldAllowPermission() {
		$this->assertTrue( $this->permissionsService->doesUserHavePermission(
			$this->testCityId, $this->staffUser, 'move' ) );
		$this->assertTrue( $this->permissionsService->doesUserHavePermission(
			$this->testCityId, $this->staffUser, 'siteadmin' ) );

		$this->assertFalse( $this->permissionsService->doesUserHavePermission(
			$this->testCityId, $this->anonUser, 'siteadmin' ) );
		$this->assertFalse( $this->permissionsService->doesUserHavePermission(
			$this->testCityId, $this->anonUser, 'move' ) );
	}

	public function testShouldAllowAllPermissions() {
		$this->assertTrue( $this->permissionsService->doesUserHaveAllPermissions(
			$this->testCityId, $this->staffUser, array( 'move', 'edit' ) ) );
		$this->assertFalse( $this->permissionsService->doesUserHaveAllPermissions(
			$this->testCityId, $this->staffUser, array( 'move', 'something-made-up' ) ) );
	}

	public function testShouldAllowAnyPermission() {
		$this->assertTrue( $this->permissionsService->doesUserHaveAnyPermission(
			$this->testCityId, $this->staffUser, array( 'move', 'edit' ) ) );
		$this->assertTrue( $this->permissionsService->doesUserHaveAnyPermission(
			$this->testCityId, $this->staffUser, array( 'move', 'something-made-up' ) ) );
		$this->assertFalse( $this->permissionsService->doesUserHaveAnyPermission(
			$this->testCityId, $this->staffUser, array( 'something-made-up1', 'something-made-up2' ) ) );
	}
}