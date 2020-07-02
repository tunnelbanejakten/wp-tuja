<?php


namespace tuja\util\concurrency;


class LockValuesList {
	private $values = [];

	public function __construct( array $values = [] ) {
		$this->values = $values;
	}

	public function add_value( string $id, int $value ): LockValuesList {
		$this->values[ $id ] = $value;

		return $this;
	}

	public function get_valid_ids( LockValuesList $reference_list ): array {
		return array_keys( array_filter( $this->values, function ( int $value, string $id ) use ( $reference_list ) {
			if ( isset( $reference_list->values[ $id ] ) ) {
				return $value === $reference_list->values[ $id ];
			} else {
				return true;
			}
		}, ARRAY_FILTER_USE_BOTH ) );
	}

	public function get_invalid_ids( LockValuesList $reference_list ): array {
		return array_keys( array_filter( $this->values, function ( int $value, string $id ) use ( $reference_list ) {
			if ( isset( $reference_list->values[ $id ] ) ) {
				return $value !== $reference_list->values[ $id ];
			} else {
				return false;
			}
		}, ARRAY_FILTER_USE_BOTH ) );
	}

	public function to_string(): string {
		return serialize( $this->values );
	}

	public static function from_string( string $input ): LockValuesList {
		return new LockValuesList( unserialize( $input ) );
	}

	public function get_ids() {
		return array_keys( $this->values );
	}

	public function subset( array $ids ): LockValuesList {
		$keys_to_pick = array_intersect( array_keys( $this->values ), $ids );
		$values       = $this->values;

		return new LockValuesList( array_combine( $keys_to_pick, array_map( function ( $key ) use ( $values ) {
			return $values[ $key ];
		}, $keys_to_pick ) ) );
	}
}