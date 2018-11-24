<?php

namespace util;

use DateTime;
use tuja\data\model\Message;


class MessageImporter
{
    private $person_dao;
    private $message_dao;
    private $people;

    public function __construct($person_dao, $message_dao, $competition_id)
    {
        $this->person_dao = $person_dao;
        $this->message_dao = $message_dao;
        $this->people = $person_dao->get_all_in_competition($competition_id);
    }


    public function find_group_by_sender($sender)
    {
        // Try to map $sender to a group. It can be a phone number, a name or an e-mail address.
    }

    public function import($text, $image_ids, $sender, $timestamp)
    {
        // 1. Try to map the sender to a group. The sender can be a phone number, a name or an e-mail address.

        $matching_people = array_filter($this->people, function ($person) use ($sender) {
            return $person->phone == $sender;
        });

        $registered_sender = !empty($matching_people) ? join(', ', array_map(function ($person) {
            return sprintf('%s i lag %d', $person->name, $person->group_id);
        }, $matching_people)) : 'okänd';

        printf('<p><strong>Från %s (registrerad avsändare: %s) den %s</strong></p>',
            $sender,
            $registered_sender,
            $timestamp->format('Y-m-d H:i:s'));


        // 2. Read the image and save it in a persistent folder. Use the MD5 hash of the file contents as the filename.
        //    Can https://codex.wordpress.org/Function_Reference/wp_handle_upload be used for this?

        // 3. Save a message object in the database (with the path to where the image was saved).

        // TODO: Rename to $group_id
        $team_id = count($matching_people) == 1 ? reset($matching_people)->group_id : null;

        $message = new Message();
        $message->form_question_id = null;
        $message->team_id = $team_id;
        $message->text = $text;
        $message->image = join(',', $image_ids);
        $message->source = 'mms';
        $message->source_message_id = sprintf('%s,%s', $sender, $timestamp->format(DateTime::ISO8601));

        $res = $this->message_dao->create($message);

        if ($res === false) {
            // TODO: Trying to reimport existing messages should not cause error
            printf('<p>Sparade inte meddelande %s i databasen pga. databasfel.</p>', $message->source_message_id);
        }

        printf('<p>Importerade bilderna %s med id=%s</p>', $message->image, $message->source_message_id);

        // 4. Create thumbnail?
    }
}