<?php

namespace tuja\admin;

use tuja\controller\payments\MatchPaymentResult;
use tuja\controller\PaymentsController;
use tuja\data\model\Group;
use tuja\data\model\payment\PaymentTransaction;
use tuja\data\store\GroupDao;
use tuja\data\store\PaymentDao;

class PaymentsMatch extends Payments {

	const ACTION_NAME_START = 'link_payments';

	const VIEW_DEFAULT = 'views/payments-import.php';
	const VIEW_RESULT  = 'views/payments-import-result.php';

	const DONT_LINK_PAYMENT = '0';
	const FIELD_LINK        = 'tuja_link_payment';
	const FIELD_AMOUNT      = 'tuja_link_amount';
	const FIELD_LINK_SEP    = '__';

	private $group_dao;

	public function __construct() {
		parent::__construct();
		$this->group_dao = new GroupDao();
	}

	public function handle_post() {
		if ( ! isset( $_POST['tuja_action'] ) ) {
			return;
		}

		if ( $_POST['tuja_action'] == self::ACTION_NAME_START ) {
			$transactions = $this->payment_dao->get_all_in_competition( $this->competition->id );

			$overall_success = array_reduce(
				$transactions,
				function ( bool $result, PaymentTransaction $transaction ) {
					$success          = true;
					$action_field_key = join( self::FIELD_LINK_SEP, array( self::FIELD_LINK, $transaction->id ) );
					$amount_field_key = join( self::FIELD_LINK_SEP, array( self::FIELD_AMOUNT, $transaction->id ) );
					if ( isset( $_POST[ $action_field_key ] ) && self::DONT_LINK_PAYMENT !== $_POST[ $action_field_key ] ) {
						$amount   = intval( $_POST[ $amount_field_key ] );
						$group_id = intval( $_POST[ $action_field_key ] );
						if ( $amount > 0 ) {
							$success = $this->payment_dao->create_group_payment( $group_id, $amount * 100, $transaction->id, '' );
						}
					}

					return $result && $success;
				},
				true
			);

			if ( $overall_success ) {
				AdminUtils::printSuccess( 'Klart.' );
			} else {
				AdminUtils::printError( 'Något gick fel.' );
			}
		}
	}

	public function output() {
		$this->handle_post();

		$transactions = $this->payment_dao->get_all_in_competition( $this->competition->id );

		$controller   = new PaymentsController( $this->competition->id );
		$match_result = $controller->match_transactions( $transactions );

		$auto_casting_hack = '___';

		$all_groups_options = array_merge(
			array( $auto_casting_hack . self::DONT_LINK_PAYMENT => 'Gör inget' ),
			array_reduce(
				$this->group_dao->get_all_in_competition( $this->competition->id ),
				function ( array $res, Group $group ) use ( $auto_casting_hack ) {
					$res[ $auto_casting_hack . $group->id ] = 'Koppla belopp till ' . $group->name;
					return $res;
				},
				array()
			)
		);

		$actions_by_transaction_key = array_reduce(
			$match_result->transactions,
			function ( array $carry, MatchPaymentResult $result ) use ( $all_groups_options, $auto_casting_hack ) {
				$amount_major_unit                 = $result->transaction->amount / 100;
				$groups_attribution_sum_major_unit = $result->transaction->groups_attribution_sum / 100;
				$selected_option                   = self::DONT_LINK_PAYMENT;
				$parts                             = array();
				if ( null !== $result->best_match ) {
					$selected_option = $result->best_match->id;
				}
				$parts[] = sprintf(
					'<select size="1" name="%s" style="width: 25em">%s</select>',
					join( self::FIELD_LINK_SEP, array( self::FIELD_LINK, $result->transaction->id ) ),
					join(
						array_map(
							function ( $value, $label ) use ( $selected_option, $auto_casting_hack ) {
								$unhacked_value = intval( substr( $value, strlen( $auto_casting_hack ) ) );
								return sprintf( '<option %s value="%s">%s</option>', $unhacked_value === $selected_option ? 'selected="selected"' : '', $unhacked_value, $label );
							},
							array_keys( $all_groups_options ),
							array_values( $all_groups_options )
						)
					)
				);

				$parts[] = sprintf( 'Belopp:<input type="number" name="%s" size="10" value="%d">', join( self::FIELD_LINK_SEP, array( self::FIELD_AMOUNT, $result->transaction->id ) ), $amount_major_unit - $groups_attribution_sum_major_unit );

				if ( null !== $result->best_match_reason ) {
					$parts[] = AdminUtils::tooltip( $result->best_match_reason );
				}

				$carry[ $result->transaction->key ] = join( '', $parts );

				return $carry;
			},
			array()
		);

		include( 'views/payments-match.php' );
	}
}
