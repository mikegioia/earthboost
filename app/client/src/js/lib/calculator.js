/**
 * Carbon Calculator Library
 *
 * This library reads in questions and answers, and returns
 * objects that can be sent to the templates. It will figure
 * out the right question to show, fill in the answers if there
 * are any, and add in any extra form controls.
 */
var Calculator = (function () {
    'use strict';

    var GROUP_TYPES = {
        home: 'home',
        office: 'office'
    };
    var MODES = {
        user: 'user',
        group: 'group'
    };
    var TYPES = {
        text: 'text',
        radio: 'radio',
        number: 'number',
        select: 'select',
        checkbox: 'checkbox'
    };
    var GROUPS = {
        energy: 'Energy',
        home_office: 'Home and Office',
        transportation: 'Transportation'
    };

    /**
     * Loads a question by index.
     * @param Array questions
     * @param Array answers
     * @param Integer userId Optional
     * @param String questionId
     * @param Object group
     * @return Object
     */
    function get ( allQuestions, answers, userId, questionId, group )
    {
        var answers = getAnswers( answers, userId );
        var mode = ( userId ) ? MODES.user : MODES.group;
        var questions = prepQuestions( allQuestions, group, mode );
        var question = findQuestion( questions, questionId );

        // If no question ID came in, then load the landing page
        // showing them their progress or telling them how to begin.
        if ( ! questionId || ! question.id ) {
            return overview( questions, answers );
        }

        return buildQuestion(
            question,
            answers.array[ questionId ],
            questions,
            allQuestions,
            group,
            mode );
    }

    /**
     * Returns the answers for the group/user. These are indexed
     * by question ID.
     * @param Array answers
     * @return Array
     */
    function getAnswers ( answers, userId ) {
        var count = 0;
        var array = [];

        if ( ! answers ) {
            return [];
        }

        answers.forEach( function ( answer ) {
            if ( answer.user_id == userId ) {
                count++;
                array[ answer.question_id ] = answer;
            }
        });

        return {
            count: count,
            array: array
        };
    }

    /**
     * Removes skipped questions.
     * @param Array questions
     * @param Object group
     * @param String mode
     * @return Array
     */
    function prepQuestions ( questions, group, mode ) {
        var array = [];
        var groupMode = mode + ':' + group.type;

        questions.forEach( function ( question ) {
            if ( question.skip_for
                && ( question.skip_for.indexOf( mode ) > -1
                    || question.skip_for.indexOf( groupMode ) > -1 ) )
            {
                return;
            }

            array.push( question );
        });

        return array;
    }

    /**
     * Locate a question by ID.
     * @param Array questions
     * @param String id
     * @return Object
     */
    function findQuestion ( questions, id ) {
        var i;

        for ( i in questions ) {
            if ( questions[ i ].id == id ) {
                return questions[ i ];
            }
        }

        return {};
    }

    /**
     * Looks for the correct question to "go to". Some questions are
     * skipped for group or user mode, and if a question points to a
     * skipped question, then we need to find the NEXT one that applies.
     * @param String gotoId Question ID to travel to
     * @param Array allQuestions
     * @param String mode
     * @return String
     */
    function findGoto ( gotoId, allQuestions, mode ) {
        var i;
        var q;

        for ( i in allQuestions ) {
            q = allQuestions[ i ];

            if ( q.id != gotoId ) {
                continue;
            }

            if ( q.skip_for && q.skip_for.indexOf( mode ) > -1 ) {
                gotoId = q.goto;
                continue;
            }

            return q.id;
        }

        return gotoId;
    }

    /**
     * Extends a question object to be used in the template.
     * @param Object question
     * @param Object answer
     * @param Array questions Skipped questions removed
     * @param Array allQuestions Contains every question for goto lookups
     * @param Object group
     * @param String mode One of 'group' or 'user'
     * @return Object
     */
    function buildQuestion ( question, answer, questions, allQuestions, group, mode ) {
        var i;
        var index = 0;
        var groupTotal = 0;
        var selectVal = answer ? answer.select : '';
        var groupNoun = ( group.type === GROUP_TYPES.office )
            ? 'employees'
            : 'family members';
        var groupFillIn = ( mode === MODES.group )
            ? 'or your ' + groupNoun
            : '';
        var q = {
            select: false,
            choices: false,
            id: question.id,
            type: question.type,
            hint: question.hint,
            label: question.label,
            heading: question.heading,
            group: GROUPS[ question.group ],
            value: answer ? answer.answer : '',
            input_suffix: question.input_suffix,
            radio: question.type == TYPES.radio,
            number: question.type == TYPES.number,
            checkbox: question.type == TYPES.checkbox,
            goto: findGoto( question.goto, allQuestions, mode )
        };

        // Get the index position in the group, and the total
        for ( i in questions ) {
            if ( questions[ i ].group != question.group ) {
                continue;
            }

            groupTotal++;

            if ( questions[ i ].id == question.id ) {
                index = groupTotal;
            }
        }

        // If this is a radio question, we want to build the list of
        // options, marking the selected one.
        if ( question.choices ) {
            q.choices = [];
            question.choices.forEach( function ( choice ) {
                q.choices.push({
                    name: choice.name,
                    label: choice.label,
                    value: choice.value,
                    goto: findGoto( choice.goto, allQuestions, mode ),
                    // @TODO split string for checkboxes and check inArray
                    selected: choice.value.toString() == q.value.toString()
                });
            });
        }

        // Set up the select items if there's a select menu.
        if ( question.select ) {
            q.select = [];

            for ( i in question.select ) {
                q.select.push({
                    label: i,
                    value: question.select[ i ],
                    selected: question.select[ i ].toString() == selectVal
                });
            }
        }

        q.index = index;
        q.total = groupTotal;
        q.label = q.label.replace( '%GROUP%', groupFillIn );
        q.progress = Math.round( (index - 1) / groupTotal * 100 );

        return q;
    }

    /**
     * Contains all of the overview info.
     * @param Array questions
     * @param Array answers
     * @return Object
     */
    function overview ( questions, answers ) {
        var i;
        var gotoId;
        var question;
        var groups = {
            transportation: {
                total: 0,
                answered: 0,
                icon: "train",
                name: "Transportation"
            },
            energy: {
                total: 0,
                answered: 0,
                name: "Energy",
                icon: "lightbulb"
            },
            home_office: {
                total: 0,
                answered: 0,
                icon: "home",
                name: "Home and Office"
            }
        };

        for ( i in questions ) {
            question = questions[ i ];
            groups[ question.group ][ 'total' ] += 1;

            if ( answers.array[ question.id ] ) {
                groups[ question.group ][ 'answered' ] += 1;
            }
            else {
                if ( ! gotoId ) {
                    gotoId = question.id;
                }
            }
        }

        // Get groups back into an array
        groups = Object.keys( groups ).map(
            function ( key ) {
                return groups[ key ];
            });

        return {
            goto: gotoId,
            groups: groups,
            show_intro: true,
            start_id: questions[ 0 ].id,
            answer_count: answers.count,
            complete: answers.count >= questions.length,
            progress: Math.round(
                Math.min( answers.count, questions.length )
                    / questions.length
                    * 100 )
        };
    }

    return {
        get: get,
        TYPES: TYPES
    };
}( Const, Mustache ));
