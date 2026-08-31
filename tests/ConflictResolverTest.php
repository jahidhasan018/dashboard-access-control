<?php
declare(strict_types=1);

namespace DashboardAccessControl\Tests\RoleAccess;

use DashboardAccessControl\Core\Constants;
use DashboardAccessControl\RoleAccess\ConflictResolver;
use DashboardAccessControl\Support\Options;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the ConflictResolver.
 *
 * @covers \DashboardAccessControl\RoleAccess\ConflictResolver
 */
class ConflictResolverTest extends TestCase {

	private ConflictResolver $resolver;

	protected function setUp(): void {
		parent::setUp();
		$this->resolver = new ConflictResolver( new Options() );
	}

	public function test_merge_empty_profiles_returns_empty_array(): void {
		$result = $this->resolver->merge( [] );
		$this->assertIsArray( $result );
	}

	public function test_merge_single_profile_returns_that_profile(): void {
		$profile = [
			Constants::PROFILE_MENUS   => [ [ 'slug' => 'edit.php', 'hidden' => true ] ],
			Constants::PROFILE_WIDGETS => [],
		];

		$result = $this->resolver->merge( [ $profile ] );
		$this->assertSame( $profile, $result );
	}
}
