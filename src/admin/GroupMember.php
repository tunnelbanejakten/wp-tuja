<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\Person;
use tuja\data\store\PersonDao;
use tuja\data\model\ValidationException;
use tuja\frontend\router\PersonEditorInitiator;
use tuja\frontend\router\ReportPointsInitiator;

class GroupMember extends Group {

	private $no_referrer_group = null;

	public function __construct() {
		parent::__construct();

		$this->person_dao     = new PersonDao();
		$this->is_create_mode = ! is_numeric( $_GET['tuja_person'] );
		if ( ! $this->is_create_mode ) {
			$this->person = $this->person_dao->get( intval( $_GET['tuja_person'] ) );

			$this->assert_set( 'Could not find person', $this->person );
		} else {
			$person = new Person();
			$person->set_type( Person::PERSON_TYPE_REGULAR );
			$person->set_status( Person::DEFAULT_STATUS );
			$this->person = $person;
		}

		$this->no_referrer_group = new \tuja\data\model\Group();
		$this->no_referrer_group->name = 'Ingen';
		$this->no_referrer_group->id = 0;
	}

	public function handle_post() {
		if ( ! isset( $_POST['tuja_action'] ) ) {
			return;
		}

		@list( $action, $parameter ) = explode( '__', @$_POST['tuja_action'] );

		if ( $action === 'save' ) {
			$this->save_person();
		} elseif ( $action === 'transition' ) {
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

	private function save_person() {
		$person = $this->person;

		$props = array(
			'name',
			'email',
			'phone',
			'pno',
			'food',
			'note',
			'role',
			'referrer_team_id',
		);

		$is_updated = false;
		foreach ( $props as $prop ) {
			$new_value     = @$_POST[ 'tuja_person_property__' . $prop ];
			$current_value = 'role' === $prop ? $person->get_type() : $person->{$prop};
			if ( $current_value != $new_value ) {
				switch($prop) {
					case 'role':
						$person->set_type( $new_value );
						break;
					case 'referrer_team_id':
						$person->referrer_team_id = $new_value != $this->no_referrer_group->id ? intval( $new_value ) : null;
						break;
					default:
						$person->{$prop} = $new_value;
						break;
				}

				$is_updated = true;
			}
		}

		if ( $is_updated ) {
			$success = false;
			try {
				if ( $this->is_create_mode ) {
					$person->group_id = $this->group->id;
					$new_person_id    = $this->person_dao->create( $person );
					$success          = $new_person_id !== false;
					$person_id        = $new_person_id;
				} else {
					$affected_rows = $this->person_dao->update( $person );
					$success       = $affected_rows !== false;
					$person_id     = $person->id;
				}
				if ( $success ) {
					$this->is_create_mode = false;
					$this->person         = $this->person_dao->get( $person_id );

					foreach ( $props as $prop ) {
						$_POST[ 'tuja_person_property__' . $prop ] = null;
					}

					AdminUtils::printSuccess(
						sprintf(
							'<span id="tuja_group_member_save_status" data-new-person-id="%d">Ändringarna har sparats.</span>',
							$person_id
						)
					);
				} else {
					AdminUtils::printError( 'Alla ändringar kunde inte sparas.' );
				}
			} catch ( ValidationException $e ) {
				AdminUtils::printException( $e );
			} catch ( Exception $e ) {
				AdminUtils::printException( $e );
			}
		}
	}

	protected function create_menu( string $current_view_name, array $parents ): BreadcrumbsMenu {
		$menu = parent::create_menu( $current_view_name, $parents );

		if ( ! $this->is_create_mode ) {
			$people_current = null;
			$people_links   = array();
			$people         = $this->person_dao->get_all_in_group( $this->group->id, true );
			foreach ( $people as $person ) {
				$active = isset( $this->person ) && $person->id === $this->person->id;
				if ( $active ) {
					$people_current = $person->get_short_description();
				}
				$link           = add_query_arg(
					array(
						'tuja_competition' => $this->competition->id,
						'tuja_view'        => $current_view_name,
						'tuja_group'       => $this->group->id,
						'tuja_person'      => $person->id,
					)
				);
				$people_links[] = BreadcrumbsMenu::item( $person->get_short_description(), $link, $active );
			}
			$menu->add(
				BreadcrumbsMenu::item( $people_current ),
				...$people_links,
			);
		} else {
			$menu->add(
				BreadcrumbsMenu::item( 'Ny person' ),
			);
		}

		return $menu;
	}

	public function output() {
		$this->handle_post();

		$is_create_mode = $this->is_create_mode;
		$group          = $this->group;
		$competition    = $this->competition;
		$is_crew_group  = $group->is_crew;
		$person         = $this->person;
		if ( $is_create_mode ) {
			$links = array();
		} else {
			$links = array_filter(
				array(
					'Redigera person via lagportal' => PersonEditorInitiator::link( $group, $person ),
					'Rapportera poäng'              => $is_crew_group ? ReportPointsInitiator::link_all( $person ) : null,
				)
			);
		}

		$person_type_dropdown = sprintf(
			'<div><select name="tuja_person_property__role">%s</select></div>',
			join(
				array_map(
					function ( $key ) use ( $person ) {
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

		$referring_group_id = isset($_POST['tuja_person_property__referrer_team_id']) ? intval($_POST['tuja_person_property__referrer_team_id']) : $person->referrer_team_id ?? $this->no_referrer_group->id;

		$referring_group_dropdown = sprintf(
			'<div><select name="tuja_person_property__referrer_team_id">%s</select></div>',
			join(
				array_map(
					function ( \tuja\data\model\Group $group ) use ( $referring_group_id ) {
						return sprintf(
							'<option value="%s" %s>%s</option>',
							$group->id,
							$referring_group_id === $group->id ? ' selected="selected"' : '',
							$group->name,
						);
					},
					array_merge(
						array($this->no_referrer_group),
						$this->group_dao->get_all_in_competition($this->competition->id, false, null)
					)
				)
			)
		);

		include 'views/group-member.php';
	}
}
