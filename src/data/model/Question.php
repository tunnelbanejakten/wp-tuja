<?php

namespace tuja\data\model;


class Question
{
    public $id;
    public $form_id;
    public $type;
    public $answer;
    public $text;
    public $sort_order;
    public $text_hint;
    public $latest_response;
}