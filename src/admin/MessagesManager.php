<?php

namespace tuja\admin;


use DateTime;
use tuja\data\model\Message;
use tuja\data\model\Competition;
use tuja\data\model\question\AbstractQuestion;
use tuja\data\model\QuestionGroup;
use tuja\data\model\Response;
use tuja\data\model\ValidationException;
use tuja\data\store\FormDao;
use tuja\data\store\GroupDao;
use tuja\data\store\MessageDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;
use tuja\data\store\ResponseDao;
use tuja\util\ImageManager;
use tuja\view\FieldImages;
use \tuja\data\model\Group;

class MessagesManager {


	private $question_group_dao;
	private $group_dao;
	private $competition;
	private $form_dao;
	private $question_dao;
	private $response_dao;
	private $message_dao;
	private $arr;

	public function __construct( Competition $competition ) {
		$this->message_dao        = new MessageDao();
		$this->question_group_dao = new QuestionGroupDao();
		$this->question_dao       = new QuestionDao();
		$this->group_dao          = new GroupDao();
		$this->form_dao           = new FormDao();
		$this->response_dao       = new ResponseDao();
		$this->competition        = $competition;
		$this->groups             = $this->group_dao->get_all_in_competition( $this->competition->id );
		$this->groups_map         = array_combine( array_map( function ( Group $group ) {
			return $group->id;
		}, $this->groups ), $this->groups );
	}

	private function get_group_list_html( Message $message ) {
		$group_option_values = array_map( function ( $group ) {
			return $group->id;
		}, $this->groups );
		$group_option_labels = array_map( function ( $group ) {
			return $group->name;
		}, $this->groups );
		$group_options       = join( array_map( function ( $value, $label ) use ( $message ) {
			return sprintf( '<option value="%s" %s>%s</option>',
				$value,
				$value == $message->group_id ? ' selected="selected"' : '',
				htmlspecialchars( $label ) );
		}, $group_option_values, $group_option_labels ) );

		return $group_options;
	}

	private function get_question_list_html() {
		$question_groups = $this->question_group_dao->get_all_in_competition( $this->competition->id );
		$questions       = $this->question_dao->get_all_in_competition( $this->competition->id );

		return join( array_map( function ( QuestionGroup $question_group ) use ( $questions ) {
			$label_prefix = ! empty( $question_group->text ) ? $question_group->text . ': ' : '';

			$questions_in_group = array_filter( $questions, function ( AbstractQuestion $question ) use ( $question_group ) {
				return $question->question_group_id == $question_group->id;
			} );

			return join( array_map( function ( AbstractQuestion $question ) use ( $label_prefix ) {
				return sprintf( '<option value="%s">%s</option>',
					$question->id,
					$label_prefix . htmlspecialchars( $question->text ) );
			}, $questions_in_group ) );
		}, $question_groups ) );
	}

	public function get_html( $messages ) {
		ob_start();
		?>
        <div class="tuja-admin-messages">
			<?php
			foreach ( $messages as $message ) {
				print $this->get_message_html( $message );
			}
			?>
        </div>
		<?php
		return ob_get_clean();
	}

	private function get_message_html( Message $message ) {
		if ( is_array( $message->image_ids ) && count( $message->image_ids ) > 0 ) {
			$images_html = array_map( function ( $image_id ) use ( $message ) {
				return AdminUtils::get_image_thumbnails_html(
					json_encode( [ 'images' => [ $image_id ] ] ),
					isset( $this->groups_map[ $message->group_id ] ) ? $this->groups_map[ $message->group_id ]->random_id : null );
			}, $message->image_ids );
		} else {
			$images_html = [];
		}
		?>
        <div class="tuja-admin-message">
            <div class="tuja-admin-message-preview">
				<?= join( '', $images_html ) ?>
            </div>
            <div class="tuja-admin-message-metadata">
                <p>
                    <strong>Mottaget:</strong><br>
					<?= $message->date_received->format( DateTime::ISO8601 ) ?>
                </p>
                <p>
                    <strong>Från:</strong><br>
					<?= $message->source_message_id ?>
                </p>
                <p>
                    <strong>Text:</strong><br>
					<?= $message->text ?>
                </p>
                <p>
                    <strong>Använd som svar på fråga:</strong><br>
                    <select name="tuja_messagesmanager__<?= $message->id ?>__group">
                        <option value="">Välj lag</option>
						<?= $this->get_group_list_html( $message ) ?>
                    </select>
                    <select name="tuja_messagesmanager__<?= $message->id ?>__question">
                        <option value="">Välj fråga</option>
						<?= $this->get_question_list_html() ?>
                    </select>
                    <button class="button" name="tuja_action" value="messagesmanager_create__<?= $message->id ?>">
                        Skapa svar
                    </button>
                </p>
                <p>
                    <small>
                        OBS: Om du skapar ett svar, byter lag för meddelandet och sedan skapar ett nytt svar så kommer
                        det första svaret inte längre kunna visas.
                    </small>
                </p>
            </div>
        </div>
		<?php
	}

	public function handle_post() {
		if ( ! isset( $_POST['tuja_action'] ) ) {
			return;
		}

		if ( strpos( $_POST['tuja_action'], 'messagesmanager_create__' ) !== false ) {
			$message_id = substr( $_POST['tuja_action'], strlen( 'messagesmanager_create__' ) );

			$question_id = (int) $_POST[ 'tuja_messagesmanager__' . $message_id . '__question' ];
			$group_id    = (int) $_POST[ 'tuja_messagesmanager__' . $message_id . '__group' ];

			$message = $this->message_dao->get( $message_id );

			$image_manager = new ImageManager();
			foreach ( $message->image_ids as $image_id ) {
				$old_group     = $this->group_dao->get( $message->group_id );
				$old_group_key = isset( $old_group ) ? $old_group->random_id : null;
				$new_group     = $this->group_dao->get( $group_id );
				$new_group_key = isset( $new_group ) ? $new_group->random_id : null;
				$image_manager->move_to_group( $image_id, $old_group_key, $new_group_key );
			}

			$message->form_question_id = $question_id;
			$message->group_id         = $group_id;

			try {
				if ( $this->message_dao->update( $message ) !== false ) {
				} else {
					AdminUtils::printError( 'Could not update message.' );
				}
			} catch ( ValidationException $e ) {
				AdminUtils::printException( $e );
			}

			// TODO: One function for moving image, one for creating response.
			$response                   = new Response();
			$response->group_id         = $group_id;
			$response->form_question_id = $question_id;
			$response->is_reviewed      = false;
			$response->submitted_answer = [
				json_encode( [
					'images'  => $message->image_ids,
					'comment' => $message->text
				] )
			];

			$created_response = $this->response_dao->create( $response );
			if ( ! $created_response ) {
				AdminUtils::printError( 'Could not create response.' );
			}
		}
	}
}