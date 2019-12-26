<?php


namespace tuja\util\ticket;


use tuja\data\model\Group;
use tuja\data\model\Ticket;

interface CouponToTicket {
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