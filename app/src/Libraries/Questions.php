<?php

namespace App\Libraries;

use App\Entities\Group
  , App\Entities\Answer
  , App\Exceptions\NotFound as NotFoundException;

/**
 * Loads the questions for the Carbon Calculator. This
 * can also read in a set of a answers and store that
 * with each question.
 */
class Questions
{
    private $answer;
    private $answers;
    private $questions;

    const TYPE_RADIO = 'radio';
    const TYPE_NUMBER = 'number';
    const TYPE_CHECKBOX = 'checkbox';

    /**
     * User storage keys from the questions asked.
     */
    const WASTE = 'WA';
    const HOME_AREA = 'HA';
    const MEAT_DAYS = 'MD';
    const BUSES_LONG = 'BL';
    const CARS_MILES = 'CM';
    const ENERGY_GAS = 'EG';
    const ENERGY_OIL = 'OI';
    const HOTEL_DAYS = 'HD';
    const TRAINS_LONG = 'TL';
    const BUSES_SHORT = 'BS';
    const WEB_SERVERS = 'WS';
    const OFFICE_AREA = 'OA';
    const HOME_PEOPLE = 'HP';
    const BUSES_MEDIUM = 'BM';
    const FLIGHTS_LONG = 'FL';
    const TRAINS_SHORT = 'TS';
    const SUBWAYS_LONG = 'SL';
    const ENERGY_POWER = 'EP';
    const TRAINS_MEDIUM = 'TM';
    const FLIGHTS_SHORT = 'FS';
    const SUBWAYS_SHORT = 'SS';
    const FLIGHTS_MEDIUM = 'FM';
    const ENERGY_PROPANE = 'EP';

    /**
     * @param array $questions
     */
    public function __construct( array $questions )
    {
        $this->questions = $questions;
    }

    /**
     * Commits an answer for a question. This method updates the
     * answers table with the user's answer data for either a group
     * or a user profile.
     * @param Answer $answer
     * @param string $value
     * @param string|null $select
     * @throws NotFoundException
     */
    public function saveAnswer( Answer $answer, $value, $select )
    {
        $select = $select ?: NULL;
        $question = $this->findQuestion( $answer->question_id );

        if ( ! $question ) {
            throw new NotFoundException( QUESTION, $answer->question_id );
        }

        $answer->save([
            'answer' => $value,
            'select' => $select
        ]);

        // Check if the answer for this question should update any
        // other question's answer. For example, if you save 3 long
        // flights, then mark the question asking if any flights were
        // taken equal to "1".
        if ( isset( $question->update ) ) {
            foreah ( $question->update as $updateId => $updateVal ) {
                $cloned = clone $answer;
                $cloned->id = NULL;
                $cloned->question_id = $updateId;
                $cloned->save([
                    'answer' => $updateVal
                ]);
            }
        }

        // Check if the answer for this question should "clear" any
        // answers for other questions. For example, if you choose
        // "no" to the bus rides question, all values for short,
        // medium, or long bus rides should be set to "0".
        if ( isset( $question->choices ) && $question->type == self::TYPE_RADIO ) {
            foreach ( $question->choices as $choice ) {
                if ( intval( $choice->value ) === intval( $answer->answer )
                    && isset( $choice->clear )
                    && $choice->clear )
                {
                    foreach ( $choice->clear as $clearId ) {
                        $cloned = clone $answer;
                        $cloned->id = NULL;
                        $cloned->question_id = $clearId;
                        $cloned->save([
                            'answer' => 0,
                            'select' => NULL
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Converts a set of answers to an array of emissions values for
     * each emissions type. Here we're querying the set of 'answers'
     * for the group or user, and then converting them into new or
     * updates to rows in the 'emissions' table. The emissions table
     * is later queried for the updated emissions and cost data for
     * rendering in the calculator.
     * @param array $answers
     * @param Group $group
     * @param User $user Optional, if saving for a user's profile
     */
    public function saveAsEmissions( array $answers, Group $group, User $user = NULL )
    {
        foreach ( $answers as $answer ) {
            switch ( $answer->question_id ) {
                case self::WASTE:
                    // subtract from a storage counter of 1570
                    // each of the user's selections
                case self::FLIGHTS_SHORT:
                case self::FLIGHTS_MEDIUM:
                case self::FLIGHTS_LONG:
                    // store this directly into the emissions database
                    // a lot can go into this catchall
            }
        }

    }

    /**
     * Load the requested question by ID.
     * @param string $questionId
     * @return object|null
     */
    private function findQuestion( $questionId )
    {
        foreach ( $this->questions as $question ) {
            if ( $questionId == $question->id ) {
                return $question;
            }
        }

        return NULL;
    }
}