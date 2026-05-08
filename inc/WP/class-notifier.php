<?php
/**
 * Notifier Class
 *
 * @package ChoctawNation
 * @subpackage Jobs_API
 */

namespace ChoctawNation\Jobs_API\WP;

/**
 * Notifier class
 */
class Notifier {

	/**
	 * The array of email addresses to send notifications to.
	 *
	 * @var array $emails
	 */
	private array $emails;

	/**
	 * Constructor
	 *
	 * @param string|string[] $emails A single email address or an array of email addresses to send notifications to, in addition to the admin email.
	 */
	public function __construct( string|array $emails ) {
		$total_emails = array( get_option( 'admin_email' ) );
		if ( is_array( $emails ) ) {
			$total_emails = array_merge( $total_emails, $emails );
		} else {
			$total_emails[] = $emails;
		}
		$this->emails = array_unique( $total_emails );
	}

	/**
	 * Send a notification email to the configured recipients.
	 *
	 * @param string $subject The subject of the email.
	 * @param string $message The body of the email.
	 */
	public function send_notification( string $subject, string $message ) {
		wp_mail( $this->emails, $subject, $message );
	}
}