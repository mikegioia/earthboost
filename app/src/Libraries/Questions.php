<?php

namespace App\Libraries;

/**
 * Loads the questions for the Carbon Calculator. This
 * can also read in a set of a answers and store that
 * with each question.
 */
class Questions
{
    private $answers;
    private $questions;

    /**
     * @param array $questions
     * @param array $answers
     */
    public function __construct( array $questions, array $answers = [] )
    {
        $this->answers = $answers;
        $this->questions = $questions;
    }

    /**
     * Converts a set of answers to an array of emissions
     * values for each emissions type.
     * @param array $answers
     * @return object
     */
    public function compute( array $answers = NULL )
    {
        if ( $answers ) {
            $this->answers = $answers;
        }


    }
}