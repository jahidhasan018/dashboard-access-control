<?php
declare(strict_types=1);

namespace DashboardAccessControl\Tests\Support;

use DashboardAccessControl\Support\Sanitizer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DashboardAccessControl\Support\Sanitizer
 */
class SanitizerTest extends TestCase {

	public function test_sanitize_role_id_with_valid_slug(): void {
		$result = Sanitizer::sanitize_role_id( 'administrator' );
		$this->assertSame( 'administrator', $result );
	}

	public function test_sanitize_role_id_removes_special_chars(): void {
		$result = Sanitizer::sanitize_role_id( 'admin<script>' );
		$this->assertStringNotContainsString( '<script>', $result );
	}

	public function test_sanitize_role_id_trims_whitespace(): void {
		$result = Sanitizer::sanitize_role_id( '  editor  ' );
		$this->assertSame( 'editor', $result );
	}

	public function test_sanitize_checkbox_returns_bool(): void {
		$this->assertTrue( Sanitizer::sanitize_checkbox( 'yes' ) );
		$this->assertTrue( Sanitizer::sanitize_checkbox( '1' ) );
		$this->assertFalse( Sanitizer::sanitize_checkbox( '' ) );
		$this->assertFalse( Sanitizer::sanitize_checkbox( '0' ) );
	}

	public function test_sanitize_array_of_strings(): void {
		$input = [ 'hello', '<b>world</b>', 'test' ];
		$result = Sanitizer::sanitize_array( $input );
		$this->assertSame( [ 'hello', 'world', 'test' ], $result );
	}

	public function test_sanitize_string_strips_tags(): void {
		$result = Sanitizer::sanitize_string( '<b>Hello</b>' );
		$this->assertSame( 'Hello', $result );
	}
}
