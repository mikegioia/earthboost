<?php

namespace App\Libraries;

use App\Entities\User
  , App\Entities\Group
  , App\Entities\Answer
  , App\Entities\Emissions
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

    /**
     * Waste per person
     */
    const WASTE_POUNDS = 1570;

    /**
     * Input types
     */
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
    const ENERGY_OIL = 'EO';
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
    const ENERGY_PROPANE = 'ER';

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
            foreach ( $question->update as $updateId => $updateVal ) {
                $cloned = clone $answer;
                $cloned->id = NULL;
                $cloned->question_id = $updateId;
                $cloned->save([
                    'select' => NULL,
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
     * @param Group $group
     * @param User $user Optional, if saving for a user's profile
     * @param int $year
     * @param bool $updateIsStandard If set, this will toggle the flag
     *   is_standard. If all questions are answered, is_standard gets
     *   turned off. Otherwise, it is turned on.
     */
    public function writeEmissions( Group $group, User $user, $year, $updateIsStandard = TRUE )
    {
        // Pull the answers eiter by group or user
        $answers = Answer::findByUserGroup( $group, $user, $year );

        foreach ( $answers as $answer ) {
            $value = 0;

            switch ( $answer->question_id ) {
                // Subtract each of the user's selections from a
                // storage counter of 1570
                case self::WASTE:
                    $value = self::WASTE_POUNDS;
                    foreach ( $answer->answer as $key => $sub ):
                        $value += ( is_array( $sub ) )
                            ? intval( min( $sub ) )
                            : intval( $sub );
                    endforeach;
                    break;
                // These need to be multiplied by the select value
                case self::MEAT_DAYS:
                case self::CARS_MILES:
                case self::ENERGY_GAS:
                case self::ENERGY_OIL:
                case self::ENERGY_POWER:
                case self::SUBWAYS_LONG:
                case self::SUBWAYS_SHORT:
                case self::ENERGY_PROPANE:
                    $value = $answer->answer * $answer->select;
                    break;
                // Everything else can go straight in to the emissions
                default:
                    $value = $answer->answer;
                    break;
            }

            // Save this emissions record to the database
            $emissions = new Emissions([
                'year' => $year,
                'user_id' =>$user->id,
                'group_id' => $group->id,
                'type_id' => $answer->question_id
            ]);
            // This will check before saving a duplicate
            $emissions->save([
                'value' => $value
            ]);
        }

        if ( ! $updateIsStandard || ! $user->exists() ) {
            return;
        }

        $qCount = $this->getQuestionCount( $group, USER );
        $member = $user->getMember( $group, $year, TRUE );
        $member->save([
            'is_standard' => ( count( $answers ) >= $qCount )
                ? 0
                : 1
        ]);
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

    private function getQuestionCount( Group $group, $mode )
    {
        $count = 0;

        foreach ( $this->questions as $question ) {
            if ( isset( $question->skip_for )
                && ( in_array( $mode, $question->skip_for )
                    || in_array( "$mode:{$group->type}", $question->skip_for ) ) )
            {
                continue;
            }

            $count++;
        }

        return $count;
    }
}