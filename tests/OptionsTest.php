<?php
declare(strict_types=1);

namespace DashboardAccessControl\Tests\Support;

use DashboardAccessControl\Support\Options;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Options wrapper.
 *
 * @covers \DashboardAccessControl\Support\Options
 */
class OptionsTest extends TestCase {

	private Options $options;

	protected function setUp(): void {
		parent::setUp();
		$this->options = new Options();
	}

	public function test_get_returns_default_for_missing_key(): void {
		$result = $this->options->get( 'nonexistent_key', 'default_value' );
		$this->assertSame( 'default_value', $result );
	}

	public function test_get_returns_null_for_missing_key_no_default(): void {
		$result = $this->options->get( 'nonexistent_key' );
		$this->assertNull( $result );
	}

	public function test_has_returns_false_for_missing_key(): void {
		$result = $this->options->has( 'nonexistent_key' );
		$this->assertFalse( $result );
	}
}
