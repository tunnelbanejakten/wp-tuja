<?php


namespace tuja\util\fee;


use DateTime;
use tuja\data\model\Group;

interface GroupFeeCalculator {
	function calculate_fee( Group $group, DateTime $date ): int;

	function description(): string;
}