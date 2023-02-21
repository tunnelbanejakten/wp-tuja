<?php


namespace tuja\data\model;

class DuelInvite {

	const STATUS_PENDING = 'pending';
	const STATUS_ACCEPTED = 'accepted';
	const STATUS_REJECTED = 'rejected';
	const STATUS_CANCELLED = 'cancelled';

	public $duel_id;
	public $team_id;
	public $random_id;
	public $status;
	public $status_updated_at;
}
