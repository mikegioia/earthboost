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
    // Used for caching
    var data;
    var isRendered = false;
    // Question ID constants
    var QID_INTRO = 'intro';

    /**
     * Public API method to setup the controller. This will render
     * the page's template to the main DOM section.
     */
    function setup () {
        Nav = new Components.Nav( DOM.get( 'nav' ) );
        Main = new Components.Calculator( DOM.get( 'main' ), saveAnswer );
    }

    /**
     * Called to destroy all state and any event handlers.
     */
    function tearDown () {
        isRendered = false;
        Nav && Nav.tearDown();
        Main && Main.tearDown();
    }

    /**
     * Load the question data.
     * @route /questions/:group/:year[/:userid]
     * @param Object ctx Contains URL params
     * @param Function next
     */
    function view ( ctx, next ) {
        var questionId = Request.param( ctx, 'questionid' );
console.log( ctx );
        if ( isRendered === true ) {
            renderQuestion( questionId );
            return;
        }

        Request.questions(
            Request.param( ctx, 'group' ),
            Request.param( ctx, 'year' ),
            Request.param( ctx, 'userid' ),
            function ( _data ) {
                // Check if the question exists. If not we're going to
                // halt and display an error.
                if ( ! checkQuestion( _data.questions, questionId ) ) {
                    Message.halt( 404, "That question doesn't exist." );
                    return;
                }

                data = _data;
                isRendered = true;
                DOM.title([ "Carbon Calculator", data.group.name ]);
                Nav.renderCalculator( data.group.name, data.year, data.user );
                renderQuestion( questionId );
            });
    }

    /**
     * Loads a question through the component.
     * @param String questionId
     */
    function renderQuestion ( questionId, checkExists ) {
        checkExists = checkExists || false;

        if ( checkExists && ! checkQuestion( data.questions, questionId ) ) {
            Message.halt( 404, "That question doesn't exist." );
            return;
        }

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
    }

    /**
     * Called by the Main component to save a new answer.
     * @param Object params
     * @param Function cb
     */
    function saveAnswer ( params, cb ) {
        Request.saveAnswer(
            data.group.name,
            data.year,
            data.user.id,
            params,
            function ( _data, status ) {
                data.answers = _data.answers;
                data.emissions = _data.emissions;
                data.offset_amount = _data.offset_amount;
                cb();
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

        // Allow the intro page
        if ( ! questionId
            || ! questionId.length
            || questionId == QID_INTRO )
        {
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