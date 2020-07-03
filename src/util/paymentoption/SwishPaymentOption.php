<?php


namespace tuja\util\paymentoption;


use tuja\data\model\Group;
use tuja\util\Strings;
use tuja\util\Template;

class SwishPaymentOption implements PaymentOption {

	private $description;
	private $account_number;
	private $message_template;
	private $message_readonly;
	private $amount_readonly;
	private $generate_qr_code;

	function render( Group $group, int $fee ): string {
		$html_sections = [];

		$message = substr( Template::string( $this->message_template )->render( Template::group_parameters( $group ) ), 0, 50 );
		if ( $this->description ) {
			$html_sections[] = sprintf( '<p>%s</p>', $this->description );
		}

		if ( $this->generate_qr_code ) {
			try {
				$swish_qr_code_image_url = ( new SwishQrCode() )->swish_qr_code_image_url(
					$this->account_number,
					$fee,
					$message,
					! $this->amount_readonly,
					! $this->message_readonly );
				$html_sections[]         = sprintf( '<div class="tuja-swish-qr-code-wrapper"><img class="tuja-swish-qr-code" src="%s" alt="QR-kod som kan scannas av Swish-appen"></div>', $swish_qr_code_image_url );
			} catch ( Exception $e ) {
			}
		}

		$html_sections[] = sprintf( '
			<div class="tuja-swish-details">
			    <div>
			        <div>%s</div>
			        <div>%s</div>
			    </div>
			    <div>
			        <div>%s</div>
			        <div>%s</div>
			    </div>
			    <div>
			        <div>%s</div>
			        <div>%s</div>
			    </div>
			</div>',
			Strings::get( 'swishpaymentoption.payee' ),
			$this->account_number,
			Strings::get( 'swishpaymentoption.message' ),
			$message,
			Strings::get( 'swishpaymentoption.amount' ),
			$fee
		);

		return join( $html_sections );
	}

	function get_config_json_schema() {
		return
			[
				"description"      => [
					"title" => 'Beskrivning',
					"type"  => "string"
				],
				"account_number"   => [
					"title" => 'Swish-nummer',
					"type"  => "string"
				],
				"message_template" => [
					"title" => 'Mall för meddelandetext',
					"type"  => "string"
				],
				"message_readonly" => [
					"title"  => 'Meddelande är låst',
					"type"   => "boolean",
					"format" => "checkbox"
				],
				"amount_readonly"  => [
					"title"  => 'Belopp är låst',
					"type"   => "boolean",
					"format" => "checkbox"
				],
				"generate_qr_code" => [
					"title"  => 'Visa QR-kod',
					"type"   => "boolean",
					"format" => "checkbox"
				]
			];
	}

	function get_default_config() {
		return [
			"description"      => "",
			"account_number"   => "123...",
			"message_template" => "{{group_key}} {{group_name}}",
			"message_readonly" => true,
			"amount_readonly"  => true,
			"generate_qr_code" => true
		];
	}

	function configure( $config ) {
		$defaults               = $this->get_default_config();
		$this->description      = $config['description'] ?: $defaults['description'];
		$this->account_number   = $config['account_number'] ?: $defaults['account_number'];
		$this->message_template = $config['message_template'] ?: $defaults['message_template'];
		$this->message_readonly = isset( $config['message_readonly'] ) ? $config['message_readonly'] : $defaults['message_readonly'];
		$this->amount_readonly  = isset( $config['amount_readonly'] ) ? $config['amount_readonly'] : $defaults['amount_readonly'];
		$this->generate_qr_code = isset( $config['generate_qr_code'] ) ? $config['generate_qr_code'] : $defaults['generate_qr_code'];
	}

	function get_config() {
		return [
			"description"      => $this->description,
			"account_number"   => $this->account_number,
			"message_template" => $this->message_template,
			"message_readonly" => $this->message_readonly,
			"amount_readonly"  => $this->amount_readonly,
			"generate_qr_code" => $this->generate_qr_code
		];
	}
}