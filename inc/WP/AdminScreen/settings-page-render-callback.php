<?php
/**
 * Settings page for Choctaw Jobs API.
 * Called via Admin_Screen::render_settings_page() callback.
 *
 * @package ChoctawNation
 * @subpackage Jobs_API
 */

?>
<div class="wrap">
	<h1>Jobs API Settings</h1>

	<div id="cno-jobs-api-settings" data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>" data-rest-url="<?php echo 'cno-jobs-api/v1/settings'; ?>"></div>

	<noscript>
		This plugin relies on JavaScript to function properly. Please enable JavaScript in your browser settings and refresh the page.
	</noscript>
</div>
<?php