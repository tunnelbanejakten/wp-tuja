<?php

namespace tuja\frontend;


use Exception;
use tuja\data\model\Group;
use tuja\data\model\ValidationException;
use tuja\frontend\router\GroupHomeInitiator;
use tuja\util\Strings;

class GroupCancelSignup extends AbstractGroupView {

	const ACTION_CANCEL = 'cancel';

	public function __construct( $url, $group_key ) {
		parent::__construct( $url, $group_key, 'Avanmäl %s' );
	}

	function output() {
		$group = $this->get_group();

		$this->check_group_status( $group );

		if ( ! $this->is_edit_allowed( $group ) ) {
			return sprintf( '<p class="tuja-message tuja-message-error">%s</p>', 'Tyvärr så går det inte att avanmäla er nu.' );
		}

		if ( @$_POST[ self::ACTION_BUTTON_NAME ] == self::ACTION_CANCEL ) {
			try {
				$group->set_status( Group::STATUS_DELETED );

				$success = $this->group_dao->update( $group );

				if ( $success ) {
					printf( '<p class="tuja-message tuja-message-success">%s</p><p>%s</p>',
						Strings::get( 'group_cancel_signup.success.message' ),
						Strings::get( 'group_cancel_signup.success.message_secondary' )
					);
				} else {
					print $this->get_exception_message_html( new Exception( Strings::get( 'group_cancel_signup.failed.message' ) ) );

				}

				return;
			} catch ( ValidationException $e ) {
				print $this->get_exception_message_html( $e );

				return;
			} catch ( Exception $e ) {
				print $this->get_exception_message_html( $e );

				return;
			}
		}

		$submit_button = $this->get_submit_button_html();
		$message       = Strings::get( 'group_cancel_signup.message', $group->name );
		$home_link     = GroupHomeInitiator::link( $group );

		include( 'views/group-cancel-signup.php' );
	}

	private function get_submit_button_html() {
		return sprintf( '<div class="tuja-buttons"><button type="submit" name="%s" value="%s">%s</button></div>', self::ACTION_BUTTON_NAME, self::ACTION_CANCEL, Strings::get( 'group_cancel_signup.label' ) );
	}
}