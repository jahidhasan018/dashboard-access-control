<?php
declare(strict_types=1);

namespace DashboardAccessControl\Tests\Support;

use DashboardAccessControl\Support\Capabilities;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Capabilities helper.
 *
 * @covers \DashboardAccessControl\Support\Capabilities
 */
class CapabilitiesTest extends TestCase {

	public function test_seed_does_not_throw(): void {
		// seed() should not throw even with mocked roles.
		Capabilities::seed();
		$this->assertTrue( true );
	}

	public function test_remove_all_does_not_throw(): void {
		// remove_all() should not throw with mocked empty roles.
		Capabilities::remove_all();
		$this->assertTrue( true );
	}
}
