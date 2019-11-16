<?php

namespace tuja\util;


class StateMachine {
	private $transitions;
	private $current_state;
	private $state_changes = [];

	public function __construct( $current_state, $transitions ) {
		$this->current_state = $current_state;
		$this->transitions   = $transitions;
	}

	public function get() {
		return $this->current_state;
	}

	public function set( string $new_state ) {
		if ( $this->current_state != $new_state ) {
			if ( ! in_array( $new_state, array_keys( $this->transitions ) ) ) {
				throw new StateMachineException( 'Status ' . $new_state . ' is not defined.' );
			}
			if ( $this->get() != null ) {
				if ( ! isset( $this->transitions[ $this->current_state ] ) ) {
					throw new StateMachineException( 'No state transitions defined for ' . $this->current_state . '. Is this an old status? Is data migration needed?' );
				}
				if ( ! in_array( $new_state, $this->transitions[ $this->get() ] ) ) {
					throw new StateMachineException( 'Transition from ' . $this->get() . ' to ' . $new_state . ' not permitted. Permitted transitions: ' . join( ', ', $this->transitions[ $this->get() ] ) . '.' );
				}
			}

			$this->state_changes[] = [ $this->current_state, $new_state ];

			$this->current_state = $new_state;
		}
	}

	public function get_state_changes(): array {
		return $this->state_changes;
	}

}