<?php

namespace tuja\view;

use tuja\data\model\Person;
use tuja\util\rules\GroupCategoryRules;

class FieldPhone extends FieldText {
	public function __construct( $label, $hint = null, bool $read_only = false, bool $compact = false ) {
		parent::__construct(
			$label,
			$hint,
			$read_only,
			array(
				'type'    => 'tel',
				'pattern' => GroupCategoryRules::PHONE_PATTERN,
			),
			$compact
		);
	}
}
