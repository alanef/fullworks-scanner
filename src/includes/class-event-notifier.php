<?php
/*
 *  @copyright (c) 2023.
 *  @author     Alan Fuller (support@fullworksplugins.com)
 *  @licence    GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 *  @link       https://fullworksplugins.com
 *
 *  This file is part of a Fullworks' Plugin.
 *
 *  This WordPress plugin  is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This WordPress plugin  is  distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  Any copying of any part of this code not in compliance with the licence terms is strictly prohibited.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with the plugin.  https://www.gnu.org/licenses/gpl-3.0.en.html
 */

/**
 * Created
 * User: alan
 * Date: 28/03/18
 * Time: 16:43
 */

namespace Fullworks_Scanner\Includes;

/**
 * Class Event_Notifier
 * @package Fullworks_Scanner\Includes
 *
 *
 * Initially will just handle emailing
 *
 */
class Event_Notifier {
	/**
	 * @var string $event
	 */
	protected $event;
	/**
	 * @var string $subject
	 */
	protected $subject;
	/**
	 * @var string $message
	 */
	protected $message;
	/**
	 * @var string $target either user or admin
	 */
	protected $target;

	/** @var Utilities $utilities */
	protected $utilities;


	/**
	 * Event_Notifier constructor.
	 *
	 * @param $utilities
	 */
	public function __construct( $utilities ) {
		$this->utilities = $utilities;
	}

	/**
	 * @param $event
	 * @param $subject
	 * @param $message
	 * @param string $target
	 */
	public function notify( $event, $subject, $message, $target = 'user' ) {
		$this->event   = $event;
		$this->subject = $subject;
		$this->message = $message;
		$this->target  = $target;
		$this->mail();
	}

	/**
	 *
	 */
	private function mail() {
		global $current_user;
		switch ( $this->target ) {
			case 'user':
				$email = $current_user->user_email;
				break;
			case 'admin':
				$options = get_option( 'FULLWORKS_SCANNER_general' );
				$email   = $options['admin_email'];
				break;
		}
		$message = apply_filters( "fvs_mail_message_{$this->event}", $this->message, $this->target );
		$subject = apply_filters( "fvs_mail_subject_{$this->event}", $this->subject, $this->target );
		if ( $message && ! wp_mail( $email, $subject, $message ) ) {
			$message = esc_html__( 'The email could not be sent.', 'fullworks-scanner' ) . "<br />\n" . esc_html__( 'Possible reason: your host may have disabled the mail() function.', 'fullworks-scanner' );
			$this->utilities->error_log( array(
				'Error'   => $message,
				'event'   => $this->event,
				'email'   => $email,
				'subject' => $subject,

			) );
			wp_die( wp_kses_post( $message ) );
		}
	}
}