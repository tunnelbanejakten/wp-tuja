<?php

namespace tuja\data\store;

use tuja\data\model\Person;
use tuja\util\DateUtils;
use tuja\util\Database;
use tuja\util\Phone;

class PersonDao extends AbstractDao
{
	const QUERY_COLUMNS = 'p.*, (DATEDIFF(CURDATE(), STR_TO_DATE(LEFT(p.pno, 8), \'%%Y%%m%%d\')) / 365.25) age';

    function __construct()
    {
		parent::__construct();
		$this->table = Database::get_table('person');
    }

    function create(Person $person)
    {
        $person->validate();

        $affected_rows = $this->wpdb->insert($this->table,
            array(
	            'random_id'       => $this->id->random_string(),
	            'name'            => $person->name,
	            'team_id'         => $person->group_id,
	            'phone'           => $person->phone,
	            'email'           => $person->email,
	            'food'            => $person->food,
	            'is_competing'    => boolval( $person->is_competing ) ? 1 : 0,
	            'is_team_contact' => boolval( $person->is_group_contact ) ? 1 : 0,
	            'pno'             => DateUtils::fix_pno( $person->pno )
            ),
            array(
                '%s',
                '%s',
                '%d',
                '%s',
	            '%s',
	            '%s',
	            '%d',
	            '%d',
                '%s'
            ));

        $success = $affected_rows !== false && $affected_rows === 1;

        return $success ? $this->wpdb->insert_id : false;
    }

    function update(Person $person)
    {
        $person->validate();

        return $this->wpdb->update($this->table,
            array(
	            'name'            => $person->name,
	            'email'           => $person->email,
	            'phone'           => $person->phone,
	            'food'            => $person->food,
	            'team_id'         => $person->group_id,
	            'is_competing'    => boolval( $person->is_competing ) ? 1 : 0,
	            'is_team_contact' => boolval( $person->is_group_contact ) ? 1 : 0,
	            'pno'             => DateUtils::fix_pno( $person->pno )
            ),
            array(
                'id' => $person->id
            ));
    }

    function get($id)
    {
        return $this->get_object(
	        function ( $row ) {
		        return self::to_person( $row );
	        },
	        'SELECT ' . self::QUERY_COLUMNS . ' FROM ' . $this->table . ' AS p WHERE id = %d',
            $id);
    }

    function get_by_key($key)
    {
        return $this->get_object(
	        function ( $row ) {
		        return self::to_person( $row );
	        },
	        'SELECT ' . self::QUERY_COLUMNS . ' FROM ' . $this->table . ' AS p WHERE random_id = %s',
            $key);
    }

	public function get_by_contact_data( $competition_id, $from ) {
		$phone = Phone::fix_phone_number( $from );
		$matches = array_filter(
			// TODO: The result of get_all_in_competition can maybe be cached to improve efficiency.
			$this->get_all_in_competition( $competition_id ),
			function ( Person $person ) use ( $phone ) {
				return Phone::fix_phone_number( $person->phone ) == $phone;
			} );
		if ( count( $matches ) == 1 ) {
			return reset( $matches );
		}

		return null;
	}

	function get_all_in_group( $group_id )
    {
        return $this->get_objects(
	        function ( $row ) {
		        return self::to_person( $row );
	        },
	        'SELECT ' . self::QUERY_COLUMNS . ' FROM ' . $this->table . ' AS p WHERE team_id = %d',
            $group_id);
    }

	function get_all_in_competition( $competition_id )
    {
        return $this->get_objects(
	        function ( $row ) {
		        return self::to_person( $row );
	        },
	        'SELECT ' . self::QUERY_COLUMNS . ' ' .
	        'FROM ' . $this->table . ' AS p INNER JOIN ' . Database::get_table( 'team' ) . ' AS t ON p.team_id = t.id ' .
	        'WHERE t.competition_id = %d',
            $competition_id);
    }

	public function delete_by_key( $key )
    {
        $affected_rows = $this->wpdb->delete($this->table, array(
            'random_id' => $key
        ));
        return $affected_rows !== false && $affected_rows === 1;
    }

	protected static function to_person( $result ): Person {
		$p                   = new Person();
		$p->id               = $result->id;
		$p->random_id        = $result->random_id;
		$p->name             = $result->name;
		$p->group_id         = $result->team_id;
		$p->phone            = Phone::fix_phone_number( $result->phone ); // TODO: Should normalizing the phone number be something we do when we read it from the database? Why not when stored?
		$p->phone_verified   = $result->phone_verified;
		$p->email            = $result->email;
		$p->email_verified   = $result->email_verified;
		$p->is_competing     = $result->is_competing != 0;
		$p->is_group_contact = $result->is_team_contact != 0;
		$p->food             = $result->food;
		$p->pno              = $result->pno;
		$p->age              = $result->age;

		return $p;
	}
}