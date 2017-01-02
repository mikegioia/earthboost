<?php

namespace App\Entities;

use App\Entity
  , App\Entities\Group
  , App\Models\Answer as AnswerModel;

class Answer extends Entity
{
    public $id;
    public $answer;
    public $select;
    public $user_id;
    public $event_id;
    public $group_id;
    public $question_id;

    protected $_modelClass = 'Answer';

    static public function findByGroup( Group $group, $year )
    {
        $answers = (new AnswerModel)->fetch([
            'year' => $year,
            'group_id' => $group->id
        ]);

        return self::hydrate( $answers );
    }
}