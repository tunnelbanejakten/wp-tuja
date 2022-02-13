<?php


namespace tuja\util\ticket;


use tuja\data\model\Group;
use tuja\data\model\Ticket;

interface CouponToTicket {

	const ERROR_CODE_COUPON_ALREADY_USED = 1;
	const ERROR_CODE_INVALID_COUPON      = 2;
	const ERROR_CODE_GENERIC             = 3;

	/**
	 * @param Group $group
	 * @param string $coupon_code
	 *
	 * @return Ticket[]
	 */
	function get_tickets_from_coupon_code( Group $group, string $coupon_code ): array;

	/**
	 * @param Group $group
	 *
	 * @return Ticket[]
	 */
	function list_tickets( Group $group ): array;
}
