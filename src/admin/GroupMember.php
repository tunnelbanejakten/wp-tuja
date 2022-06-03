<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\Person;
use tuja\data\store\PersonDao;
use tuja\data\model\ValidationException;
use tuja\frontend\router\PersonEditorInitiator;
use tuja\frontend\router\ReportPointsInitiator;

class GroupMember extends AbstractGroup {

	public function __construct() {
		parent::__construct();

		$this->person_dao = new PersonDao();
		$this->person     = $this->person_dao->get( $_GET['tuja_person'] );
		if ( ! $this->person ) {
			print 'Could not find person';

			return;
		}
	}

	public function handle_post() {
		if ( ! isset( $_POST['tuja_action'] ) ) {
			return;
		}

		@list( $action, $parameter ) = explode( '__', @$_POST['tuja_action'] );

		if ( $action === 'save' ) {
			$this->update_person();
		} elseif ( $action === 'transition' ) {
			var_dump($parameter);

			$this->person->set_status( $parameter );

			$success = $this->person_dao->update( $this->person );

			if ( $success ) {
				$this->person = $this->person_dao->get( $this->person->id );
				AdminUtils::printSuccess(
					sprintf(
						'Status har ändrats till %s.',
						$this->person->get_status()
					)
				);
			} else {
				AdminUtils::printError(
					sprintf(
						'Kunde inte ändra till %s.',
						$parameter
					)
				);
			}
		}
	}

	private function update_person() {
		$person = $this->person;

		$props = array(
			'name',
			'email',
			'phone',
			'pno',
			'food',
			'note',
			'role',
		);

		$is_updated = false;
		foreach ( $props as $prop ) {
			$new_value     = @$_POST[ 'tuja_person_property__' . $prop ];
			$current_value = 'role' === $prop ? $person->get_type() : $person->{$prop};
			if ( $current_value !== $new_value ) {
				if ( 'role' === $prop ) {
					$person->set_type( $new_value );
				} else {
					$person->{$prop} = $new_value;
				}

				$is_updated = true;
			}
		}

		if ( $is_updated ) {
			$success = false;
			try {
				$affected_rows = $this->person_dao->update( $person );
				if ( $affected_rows !== false ) {
					$success      = true;
					$this->person = $this->person_dao->get( $person->id );

					foreach ( $props as $prop ) {
						$_POST[ 'tuja_person_property__' . $prop ] = null;
					}
				}
			} catch ( ValidationException $e ) {
				AdminUtils::printException( $e );
			} catch ( Exception $e ) {
				AdminUtils::printException( $e );
			}

			if ( $success ) {
				AdminUtils::printSuccess( 'Ändringarna har sparats.' );
			} else {
				AdminUtils::printError( 'Alla ändringar kunde inte sparas.' );
			}
		}
	}

	protected function create_menu( $current_view_name ) {
		$menu = parent::create_menu( $current_view_name );

		if ( $current_view_name === 'GroupMember' ) {
			$people_current = null;
			$people_links   = array();
			$people         = $this->person_dao->get_all_in_group( $this->group->id, true );
			foreach ( $people as $person ) {
				if ( $person->id === $this->person->id ) {
					$people_current = $person->name;
				}
				$link           = add_query_arg(
					array(
						'tuja_competition' => $this->competition->id,
						'tuja_view'        => $current_view_name,
						'tuja_group'       => $this->group->id,
						'tuja_person'      => $person->id,
					)
				);
				$people_links[] = BreadcrumbsMenu::item( $person->name, $link );
			}
			$menu->add(
				BreadcrumbsMenu::item( $people_current ),
				...$people_links,
			);
		}

		return $menu;
	}

	public function output() {
		$this->handle_post();

		$group       = $this->group;
		$person      = $this->person;
		$competition = $this->competition;

		$is_crew_group = $group->get_category()->get_rules()->is_crew();
		$links         = array_filter(
			array(
				'Redigera person via lagportal' => PersonEditorInitiator::link( $group, $person ),
				'Rapportera poäng'              => $is_crew_group ? ReportPointsInitiator::link_all( $person ) : null,
			)
		);

		$person_type_dropdown = sprintf(
			'<div><select name="tuja_person_property__role">%s</select></div>',
			join(
				array_map(
					function ( $key ) use ( $person ) {
						$id = uniqid();

						return sprintf(
							'<option value="%s" %s>%s</option>',
							$key,
							( @$_POST['tuja_person_property__role'] ?? $person->get_type() ) === $key ? ' selected="selected"' : '',
							Person::PERSON_TYPE_LABELS[ $key ] ?? $key,
						);
					},
					Person::PERSON_TYPES
				)
			)
		);

		include 'views/group-member.php';
	}
}
