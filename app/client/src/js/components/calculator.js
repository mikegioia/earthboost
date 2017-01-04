/**
 * Carbon Calculator Component
 */
Components.Calculator = (function ( DOM, Calculator, Message ) {
'use strict';
// Returns a new instance
return function ( $root, saveCallback ) {
    // Event namespace
    var namespace = '.calculator';
    // DOM template nodes
    var $question = DOM.get( '#question' );
    var $questions = DOM.get( '#questions' );
    // Templates
    var tpl = {
        question: DOM.html( $question ),
        questions: DOM.html( $questions )
    };
    // DOM nodes used and internal state
    var data;
    var urlParams;

    /**
     * Load the <main> element with our list of groups.
     * @param Object data {
     *   mode: String
     *   year: Integer
     *   user: Object
     *   group: Object
     *   answers: Array
     *   questions: Array
     *   emissions: Float
     *   question_id: Integer
     *   offset_amount: Float
     * }
     */
    function render ( _data ) {
        data = _data;
        updateData( data );
        $root.className = 'calculator';
        DOM.render( tpl.questions, data, tpl ).to( $root );
        focusInput();
        buttonEvents();
    }

    /**
     * Adds properties and cleans up other properties from the
     * response data.
     * @param Object data
     */
    function updateData ( data ) {
        var question = Calculator.get(
            data.questions,
            data.answers,
            data.user.id,
            data.question_id,
            data.group );

        // Save for URL generation
        urlParams = {
            year: data.year,
            userid: data.user.id,
            name: data.group.name
        };

        // Format certain numbers
        data.emissions = data.emissions.toFixed( 1 );
        data.offset_amount = data.offset_amount
            .toFixed( 2 )
            .toString()
            .numberCommas();
        // Used for links
        data.url_stem = ( data.user.id )
            ? Const.url.questions_user.supplant( urlParams )
            : Const.url.questions.supplant( urlParams );
        data.url_back = Const.url.group_year.supplant( urlParams );
        // Add the question to render
        data.question = question;
    }

    function focusInput () {
        var $number = DOM.get( 'input[type="number"]' );

        if ( $number ) {
            $number.focus();
        }
    }

    function buttonEvents () {
        var $back = DOM.get( 'button.back' );
        var $form = DOM.get( 'form[name="question"]' );

        if ( $back ) {
            $back.onclick = function ( e ) {
                window.history.back();
                e.preventDefault();
            };
        }

        if ( $form ) {
            $form.onsubmit = saveAnswer;
        }
    }

    function saveAnswer ( e ) {
        var gotoId;
        var $input;
        var $form = DOM.get( 'form[name="question"]' );
        var $answer = DOM.get( 'input[name="answer"]' );
        var answer = ( $answer ) ? $answer.value : null;
        var $select = DOM.get( 'select[name="select"]' );
        var questionId = DOM.get( 'input[name="id"]' ).value;
        var urlStem = ( data.user.id )
            ? Const.url.questions_user.supplant( urlParams )
            : Const.url.questions.supplant( urlParams );
        var inputType = $form.dataset.type;

        switch ( inputType ) {
            case Calculator.TYPES.radio:
                $input = DOM.get( 'input[name="answer"]:checked' );
                answer = ( $input ) ? $input.value : answer;
                break;
            case Calculator.TYPES.number:
                $input = DOM.get( 'input[type="number"]' );
                break;
            default:
                $input = $form;
                answer = DOM.serialize( $form );
                delete answer.id;
                break;
        }

        e.preventDefault();
        gotoId = $input ? $input.dataset.goto : null;
        saveCallback(
            {
                answer: answer,
                question_id: questionId,
                select: ( $select ) ? $select.value : null
            },
            function () {
                // Render the next question
                if ( gotoId ) {
                    page( urlStem + '/' + gotoId );
                }
                // Or take them back to the group page
                else {
                    page( Const.url.group_year.supplant( urlParams ) );
                    Message.success(
                        "Wonderful! You've finished calculating your carbon " +
                        "emissions! You can edit these answers at any time. " +
                        "Look for the <i class='icon icon-edit'></i> icon.",
                        false );
                }
            });
    }

    function tearDown () {
        tpl = {};
        data = null;
        DOM.clear( $root );
        $root.className = '';
    }

    return {
        render: render,
        tearDown: tearDown
    };
}}( DOM, Calculator, Message ));