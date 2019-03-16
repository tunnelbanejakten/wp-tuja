<?php

namespace tuja\view;

class FieldTextMulti extends Field {
	public $options;

	public function __construct( $options ) {
		parent::__construct();
		$this->options = $options;
	}

	private function is_freetext() {
		return count( $this->options ) == 0 || ( count( $this->options ) == 1 && empty( $this->options[0] ) );
	}

	public function get_posted_answer( $form_field ) {
		$user_answer = parent::get_posted_answer( $form_field );

		if ( $this->is_freetext() ) {
			return preg_split( "/[\s,]+/", $user_answer );
		} else {
			return $user_answer;
		}
	}

	public function render( $field_name ) {
		$render_id = $field_name ?: uniqid();
		$hint      = isset( $this->hint ) ? sprintf( '<br><span class="tuja-question-hint">%s</span>', $this->hint ) : '';

		$value = $this->get_posted_answer( $field_name ) ?: $this->value ?: array();

		return sprintf( '<div class="tuja-field"><label for="%s">%s%s</label>%s</div>',
			$render_id,
			$this->label,
			$hint,
			$this->is_freetext()
				// The textarea is more like a regular freetext field than FieldTextMulti?
				? sprintf( '<textarea name="%s">%s</textarea>', $field_name, htmlspecialchars( join( ', ', $value ) ) )
				: $this->render_list( $render_id, $field_name )
		);
	}

	public function render_list( $render_id, $field_name ) {
		return join( array_map( function ( $index, $value ) use ( $render_id, $field_name ) {
			$id   = $render_id . '-' . $index;
			$name = $field_name ?: $this->key;

			return sprintf( '<div class="tuja-%s"><label for="%s">%s</label><input type="text" name="%s[%d]" value="%s" class="tuja-%s tuja-%s-shortlist" id="%s" %s/></div>',
				strtolower( ( new \ReflectionClass( $this ) )->getShortName() ),
				$id,
				htmlspecialchars( $value ),
				$name,
				$index,
				htmlspecialchars( $_POST[ $name ][ $index ] ?: $this->value[ $index ] ),
				strtolower( ( new \ReflectionClass( $this ) )->getShortName() ),
				strtolower( ( new \ReflectionClass( $this ) )->getShortName() ),
				$id,
				$this->read_only ? ' disabled="disabled"' : '' );
		}, array_keys( $this->options ), array_values( $this->options ) ) );
	}
}