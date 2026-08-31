<?php
declare(strict_types=1);

namespace DashboardAccessControl\Tests\RoleAccess;

use DashboardAccessControl\RoleAccess\RoleProfileRepository;
use DashboardAccessControl\Support\Options;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the RoleProfileRepository.
 *
 * @covers \DashboardAccessControl\RoleAccess\RoleProfileRepository
 */
class RoleProfileRepositoryTest extends TestCase {

	private RoleProfileRepository $repo;

	protected function setUp(): void {
		parent::setUp();
		$this->repo = new RoleProfileRepository( new Options() );
	}

	public function test_get_all_returns_array(): void {
		$result = $this->repo->get_all();
		$this->assertIsArray( $result );
	}

	public function test_get_returns_defaults_for_missing_role(): void {
		$result = $this->repo->get( 'nonexistent_role' );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'menus', $result );
	}

	public function test_save_and_get(): void {
		$this->repo->save( 'test_role', [
			'label'  => 'Test Role',
			'menus'  => [],
		] );

		$result = $this->repo->get( 'test_role' );
		$this->assertNotNull( $result );
		$this->assertSame( 'Test Role', $result['label'] );
	}

	public function test_delete_removes_role(): void {
		$this->repo->save( 'role_to_delete', [ 'label' => 'Delete Me' ] );
		$this->repo->delete( 'role_to_delete' );

		$all = $this->repo->get_all();
		$this->assertArrayNotHasKey( 'role_to_delete', $all );
	}
}
