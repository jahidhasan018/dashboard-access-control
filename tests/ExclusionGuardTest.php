<?php
declare(strict_types=1);

namespace DashboardAccessControl\Tests\RoleAccess;

use DashboardAccessControl\RoleAccess\ExclusionGuard;
use DashboardAccessControl\RoleAccess\RoleProfileRepository;
use DashboardAccessControl\Support\Options;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the ExclusionGuard.
 *
 * @covers \DashboardAccessControl\RoleAccess\ExclusionGuard
 */
class ExclusionGuardTest extends TestCase {

	private ExclusionGuard $guard;

	protected function setUp(): void {
		parent::setUp();
		$this->guard = new ExclusionGuard(
			new RoleProfileRepository( new Options() ),
			new Options()
		);
	}

	public function test_is_excluded_returns_bool(): void {
		$result = $this->guard->is_excluded( 'administrator' );
		$this->assertIsBool( $result );
	}

	public function test_is_excluded_for_unknown_role(): void {
		$result = $this->guard->is_excluded( 'nonexistent_role_xyz' );
		$this->assertFalse( $result );
	}
}
