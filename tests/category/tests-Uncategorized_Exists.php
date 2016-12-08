<?php

class cnTests_Install extends WP_UnitTestCase {

	public function test_category_exists() {

		$term = cnTerm::getBy( 'name', 'Uncategorized', 'category' );

		if ( ! is_wp_error( $term ) ) {

			$this->assertEquals(
				array(
					'name' => 'Uncategorized',
					'slug' => 'uncategorized',
				),
				array(
					'name' => $term->name,
					'slug' => $term->slug,
				),
				'Uncategorized category not found.'
			);

		} else {

			$this->assertTrue( FALSE, 'WP Error occurred. Uncategorized category not found.' );
		}

	}

	public function test_category_exists_alternate() {

		$term = cnTerm::getBy( 'name', 'Uncategorized', 'category' );

		$this->assertFalse( is_wp_error( $term ) , 'WP Error occurred. Uncategorized category not found.');

		$this->assertEquals(
			array(
				'name' => 'Uncategorized',
				'slug' => 'uncategorized',
			),
			array(
				'name' => $term->name,
				'slug' => $term->slug,
			),
			'Uncategorized category not found.'
		);
	}
}
