---
applyTo: 'tests/**/*'
---

# PHPUnit Test Instructions

Use these instructions when writing or updating PHPUnit tests for this WordPress project.

## Goal

Write reliable integration tests that verify WordPress behavior in a realistic test environment. Prefer testing public behavior over private implementation details.

## Test Framework

Use the WordPress PHPUnit test suite and extend the appropriate WordPress base test case:

```php
class Example_Test extends WP_UnitTestCase {
	// Tests go here.
}
```

Use `WP_UnitTestCase` unless the existing project has a more specific shared base class.

## File Naming

Test files should use the following pattern:

```text
tests/test-example-feature.php
```

Test class names should describe the unit or feature under test:

```php
class Example_Feature_Test extends WP_UnitTestCase {}
```

Test method names should start with `test_` and describe the expected behavior:

```php
public function test_returns_empty_array_when_no_posts_exist() {}
```

## Setup Rules

Use `set_up()` for per-test setup.

```php
public function set_up() {
	parent::set_up();

	// Per-test setup.
}
```

Use `tear_down()` for per-test cleanup.

```php
public function tear_down() {
	// Per-test cleanup.

	parent::tear_down();
}
```

Use `set_up_before_class()` only for expensive setup that can safely be shared across every test in the class.

```php
public static function set_up_before_class() {
	parent::set_up_before_class();

	// Shared class setup.
}
```

Do not rely on state created by another test. Each test must be able to run independently.

## WordPress Hooks

When testing code that depends on WordPress hooks, make the hook timing explicit.

Good:

```php
do_action( 'init' );

$this->assertTrue( post_type_exists( 'location' ) );
```

Avoid assuming that hooks have already fired unless the bootstrap guarantees it.

If the plugin or theme code runs on `plugins_loaded`, `after_setup_theme`, `init`, or `wp_loaded`, confirm that test setup happens before that hook is triggered.

## Factories

Use WordPress factories for posts, terms, users, and comments.

```php
$post_id = self::factory()->post->create(
	array(
		'post_type'   => 'post',
		'post_status' => 'publish',
	)
);
```

Prefer factories over manually inserting database rows.

## Assertions

Use the most specific assertion available.

Prefer:

```php
$this->assertSame( 'location', get_post_type( $post_id ) );
$this->assertTrue( post_type_exists( 'location' ) );
$this->assertCount( 3, $items );
```

Avoid:

```php
$this->assertEquals( 'location', get_post_type( $post_id ) );
$this->assertNotEmpty( $items );
```

Use `assertSame()` when comparing scalar values.

## Options and Globals

When tests modify options, globals, current user, queried object, or request state, restore them in `tear_down()`.

```php
public function tear_down() {
	delete_option( 'example_option' );
	wp_set_current_user( 0 );

	parent::tear_down();
}
```

Avoid leaking state between tests.

## Plugin and Theme Loading

The test bootstrap should load the plugin or theme intentionally.

For plugins, use `_manually_load_plugin()` in `tests/bootstrap.php`.

For themes, switch to the test theme in the bootstrap or test setup when needed:

```php
switch_theme( 'theme-folder-name' );
```

Do not load production bootstrap files multiple times unless they are guarded against duplicate execution.

## Custom Post Types and Taxonomies

Register custom post types and taxonomies before creating related content.

```php
do_action( 'init' );

$post_id = self::factory()->post->create(
	array(
		'post_type' => 'location',
	)
);
```

If a test utility registers post types or taxonomies, call it before creating posts or terms.

## ACF and Field Registration

If tests depend on ACF field groups, register fields explicitly in setup.

```php
ACF_Fields::register_fields( 'locations' );
```

Avoid testing ACF internals. Test the project behavior that depends on field values.

## Test Data

Create only the data needed for the test.

Prefer clear, local test data:

```php
$term_id = self::factory()->term->create(
	array(
		'name'     => 'Health',
		'taxonomy' => 'category',
	)
);
```

Avoid relying on hard-coded IDs unless the test explicitly creates and controls them.

## Naming Pattern

Structure test names around expected behavior:

```php
public function test_get_locations_returns_only_published_locations() {}
public function test_query_excludes_locations_without_required_meta() {}
public function test_shortcode_returns_empty_string_when_location_is_missing() {}
```

## Mocking

Prefer real WordPress objects and factories over mocks.

Use mocks only for external services, network requests, or boundaries that are expensive or unreliable in tests.

## Accessibility

When testing rendered markup, include accessibility expectations when relevant.

Examples:

```php
$this->assertStringContainsString( 'aria-label=', $html );
$this->assertStringContainsString( '<button', $html );
$this->assertStringNotContainsString( 'href="#"', $html );
```

Prefer asserting semantic markup over brittle class-name checks.

## Blocks

For custom blocks, test the rendered output and registered metadata.

```php
$registry = WP_Block_Type_Registry::get_instance();

$this->assertTrue( $registry->is_registered( 'namespace/block-name' ) );
```

For dynamic blocks, test `render_block()` or the block render callback output.

## Interactivity API

When testing interactive blocks, verify server-rendered directives and state shape.

```php
$this->assertStringContainsString( 'data-wp-interactive', $html );
$this->assertStringContainsString( 'data-wp-on--click', $html );
```

Do not test browser behavior in PHPUnit. Use end-to-end tests for client-side behavior.

## HTTP Requests

Mock external HTTP requests with WordPress filters.

```php
add_filter(
	'pre_http_request',
	function () {
		return array(
			'headers'  => array(),
			'body'     => wp_json_encode(
				array(
					'success' => true,
				)
			),
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}
);
```

Remove filters after the test if they are not scoped to a single test.

## Error Conditions

Every important success path should have at least one failure-path test.

Examples:

```php
public function test_returns_wp_error_when_api_request_fails() {}
public function test_returns_empty_array_when_required_option_is_missing() {}
public function test_does_not_register_block_when_metadata_file_is_missing() {}
```

## Code Standards

Follow WordPress PHP coding standards.

Use tabs for indentation.

Add visibility to all class methods.

Do not use translation functions unless the project explicitly requires localization.

Use strict, readable assertions.

## Preferred Test Shape

Use arrange, act, assert spacing.

```php
public function test_returns_published_locations() {
	$published_id = self::factory()->post->create(
		array(
			'post_type'   => 'location',
			'post_status' => 'publish',
		)
	);

	self::factory()->post->create(
		array(
			'post_type'   => 'location',
			'post_status' => 'draft',
		)
	);

	$locations = get_locations();

	$this->assertCount( 1, $locations );
	$this->assertSame( $published_id, $locations[0]->ID );
}
```

## Running Tests

Run the full PHPUnit suite:

```bash
vendor/bin/phpunit
```

Run one test file:

```bash
vendor/bin/phpunit tests/test-example-feature.php
```

Run one test method:

```bash
vendor/bin/phpunit --filter test_returns_published_locations
```

## Common Pitfalls

Avoid these patterns:

-   Depending on hard-coded database IDs.
-   Running setup after the plugin or theme has already initialized.
-   Registering post types after content has been created.
-   Forgetting to call `parent::set_up()` or `parent::tear_down()`.
-   Testing private methods instead of public behavior.
-   Using broad assertions when specific assertions are available.
-   Leaving modified options, globals, filters, or actions behind.
-   Assuming hooks have fired without explicitly firing them in the test.

## Review Checklist

Before considering a test complete, confirm:

-   The test can run independently.
-   The test creates its own data.
-   Setup runs before the code under test needs it.
-   Assertions are specific.
-   WordPress state is cleaned up.
-   Hook timing is intentional.
-   The test name explains the expected behavior.
-   Failure paths are covered where practical.
