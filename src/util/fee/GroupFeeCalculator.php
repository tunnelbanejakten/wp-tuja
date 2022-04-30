<?php


namespace tuja\util\fee;


use DateTimeInterface;
use tuja\data\model\Group;

interface GroupFeeCalculator {
	const DEFAULT = CompetingParticipantFeeCalculator::class;

	function calculate_fee( Group $group, DateTimeInterface $date ): int;

	function description(): string;

	function configure( $config );

	function get_config_json_schema();

	function get_config();

	function get_default_config();
}