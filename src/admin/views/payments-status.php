<?php namespace tuja\admin;

use DateTime;
use tuja\controller\PaymentsController;
use tuja\data\model\Group;
use tuja\data\model\payment\GroupPayment;
use tuja\data\model\payment\PaymentTransaction;

$this->print_root_menu();
$this->print_menu();
?>

<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
	<table id="tuja_transctions_list" class="tuja-table">
		<thead>
		<tr>
			<th>Grupp</th>
			<th>Avgift</th>
			<th>Inbetalat</th>
			<th>Status</th>
		</tr>
		</thead>
		<tbody>
		<?php
		$payments_controller = new PaymentsController( $this->competition );
		array_walk(
			$groups,
			function ( Group $group ) use ( $group_payments, $payments_controller ) {
				list ($fee, $fee_paid, $status_message) = $payments_controller->group_fee_status(
					$group,
					$group_payments[ $group->id ] ?? array(),
					new DateTime()
				);

				$status_css_class = '';
				$fee_diff         = $fee - $fee_paid;
				if ( $fee_diff === 0 ) {
					$status_css_class = 'tuja-admin-review-autoscore-good';
				} elseif ( $fee_diff < 0 ) {
					$status_css_class = 'tuja-admin-review-autoscore-decent';
				} elseif ( $fee_diff > 0 ) {
					$status_css_class = 'tuja-admin-review-autoscore-poor';
				}
				printf(
					'
				<tr>
					<td><a href="%s">%s</a></td>
					<td>%s kr</td>
					<td>%s kr</td>
					<td>
						<span class="tuja-admin-review-autoscore %s">
							%s
						</span>
					</td>
				</tr>
				',
					add_query_arg(
						array(
							'tuja_group' => $group->id,
							'tuja_view'  => 'Group',
						)
					),
					$group->name,
					number_format_i18n( $fee ),
					number_format_i18n( $fee_paid ),
					$status_css_class,
					$status_message
				);
			}
		);
		?>
		</tbody>
	</table>
</form>
