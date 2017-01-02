/**
 * Questions Controller
 *
 * Loads the array of questions for the carbon calculator. This will
 * collect answers for a group or member.
 */
Pages.Questions = (function ( Request, DOM, Components, Message ) {
    'use strict';
    // Components
    var Nav;
    var Main;

    /**
     * Public API method to setup the controller. This will render
     * the page's template to the main DOM section.
     */
    function setup () {
        Nav = new Components.Nav( DOM.get( 'nav' ) );
        Main = new Components.Calculator( DOM.get( 'main' ) );
console.log( 'qestions setup' );
    }

    /**
     * Called to destroy all state and any event handlers.
     */
    function tearDown () {
        Nav && Nav.tearDown();
        Main && Main.tearDown();
console.log( 'qestions teardown' );
    }

    /**
     * Load the question data.
     * @route /questions/:group/:year[/:userid]
     * @param Object ctx Contains URL params
     * @param Function next
     */
    function view ( ctx, next ) {
        var questionId = Request.param( ctx, 'questionid' );

        Request.questions(
            Request.param( ctx, 'group' ),
            Request.param( ctx, 'year' ),
            Request.param( ctx, 'userid' ),
            function ( data ) {
                // Check if the question exists. If not we're going to
                // halt and display an error.
                if ( ! checkQuestion( data.questions, questionId ) ) {
                    Message.halt( 404, "That question doesn't exist." );
                    return;
                }

                DOM.title([ "Carbon Calculator", data.group.name ]);
                Nav.renderCalculator( data.group.name, data.year );
                Main.render({
                    mode: data.mode,
                    user: data.user,
                    year: data.year,
                    group: data.group,
                    answers: data.answers,
                    question_id: questionId,
                    questions: data.questions,
                    emissions: data.emissions,
                    offset_amount: data.offset_amount
                });
            });
    }

    /**
     * Check if the question exists.
     * @param Array questions
     * @param Integer questionId
     * @return Bool
     */
    function checkQuestion ( questions, questionId ) {
        var i;

        if ( ! questions ) {
            return false;
        }

        if ( ! questionId || ! questionId.length ) {
            return true;
        }

        for ( i in questions ) {
            if ( questions[ i ].id == questionId ) {
                return true;
            }
        }

        return false;
    }

    return {
        view: view,
        setup: setup,
        tearDown: tearDown
    };
}( Request, DOM, Components, Message ));