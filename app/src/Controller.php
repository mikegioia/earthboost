<?php

namespace App;

use App\Entity
  , Silex\Application
  , App\Entities\User
  , App\Entities\Group
  , App\Entities\Member
  , App\Entities\Answer
  , App\Libraries\Questions
  , Symfony\Component\HttpFoundation\Request
  , App\Exceptions\NoMember as NoMemberException
  , App\Exceptions\NotFound as NotFoundException
  , Symfony\Component\HttpFoundation\JsonResponse;

class Controller
{
    protected $data = [];
    protected $code = 200;
    protected $message = "";
    protected $messages = [];
    protected $status = SUCCESS;

    public function ping()
    {
        return $this->respond( SUCCESS, "Pong" );
    }

    public function error()
    {
        throw new NotFoundException;
    }

    public function login( Request $request )
    {
        // Verify the email and password. Create a new session
        // and save the cookie.
        return $this->respond( SUCCESS, "You have logged in" );
    }

    public function logout()
    {

    }

    /**
     * Records user info and redirects them to success message.
     */
    public function signup( Request $request )
    {
    }

    public function dashboard()
    {
        // @TODO AUTH, STUBBED
        // THIS SHOULD USE A USER OBJECT IN THE SESSION
        $user = new User( 1 );

        $this->data[ 'user' ] = $user;
        $this->data[ 'groups' ] = $user->getGroups();

        return $this->respond( SUCCESS );
    }

    /**
     * Prepare all of the dashboard data for a group.
     */
    public function group( $name, $year = "", Application $app )
    {
        // @TODO AUTH, STUBBED
        // THIS SHOULD USE A USER OBJECT IN THE SESSION
        $user = new User( 1 );

        $year = ( $year ) ?: date( "Y" );
        // @TODO USE FACTORY
        // THIS SHOULD HAVE PASSED A GROUP OBJECT INSTEAD OF NAME
        $group = Group::loadByName( $name );

        if ( ! $group->exists() ) {
            throw new NotFoundException( GROUP, $name );
        }

        // Prepare all of the statistics and return them
        $this->data[ 'year' ] = $year;
        $this->data[ 'user' ] = $user;
        $this->data[ 'group' ] = $group;
        $this->data[ 'groups' ] = $user->getGroups();
        $this->data[ 'locales' ] = $app[ 'locales' ];
        $this->data[ 'members' ] = $group->getMembers( $year );
        $this->data[ 'emissions' ] = $group->getEmissions( $year );
        $this->data[ 'offset_amount' ] = $group->getOffsetAmount( $year );

        return $this->respond( SUCCESS );
    }

    /**
     * Saves a group member.
     */
    public function saveMember( $name, $year, Request $request, Application $app )
    {
        $post = $request->request->all();
        $id = get( $post, 'id', NULL );
        // @TODO USE FACTORY
        // THIS SHOULD HAVE PASSED A GROUP OBJECT INSTEAD OF NAME
        $group = Group::loadByName( $name );
        $months = get( $post, 'locale_months', 12 );
        $params = [
            'name' => get( $post, 'name' ),
            'email' => get( $post, 'email' ),
            'locale' => get( $post, 'locale' ),
            'is_admin' => ( get( $post, 'is_admin' ) == 1 ) ? 1 : 0,
            'locale_percent' => round( intval( $months ) / 12 * 100 ),
            'is_champion' => ( get( $post, 'is_champion' ) == 1 ) ? 1 : 0
        ];

        if ( ! $group->exists() ) {
            throw new NotFoundException( GROUP, $name );
        }

        $member = new Member( $id ?: NULL, [
            Member::POPULATE_EMISSIONS => FALSE
        ]);
        $member->saveToGroup( $params, $group, $year );
        $member->buildProfile();

        $this->data[ 'member' ] = $member;
        $this->messages[] = [
            'type' => SUCCESS,
            'message' => ( valid( $id, INT ) )
                ? "Account info saved."
                : "New account successfully saved."
        ];

        return $this->group( $name, $year, $app );
    }

    /**
     * Removes a member.
     */
    public function removeMember( $name, $year, Request $request, Application $app )
    {
        $id = $request->request->get( 'id' );
        $member = new Member( $id );
        // @TODO USE FACTORY
        // THIS SHOULD HAVE PASSED A GROUP OBJECT INSTEAD OF NAME
        $group = Group::loadByName( $name );

        if ( ! $group->exists() ) {
            throw new NotFoundException( GROUP, $name );
        }

        if ( ! $member->exists() ) {
            throw new NotFoundException( MEMBER, $id );
        }

        $member->removeFromGroup();

        $this->messages[] = [
            'type' => SUCCESS,
            'message' => "That person was removed from {$group->label}."
        ];

        return $this->group( $name, $year, $app );
    }

    public function questions( $name, $year, $userId = NULL, Application $app )
    {
        // @TODO USE FACTORY
        // THIS SHOULD HAVE PASSED A GROUP OBJECT INSTEAD OF NAME
        $user = new User( $userId );
        $group = Group::loadByName( $name );

        if ( ! valid( $userId, INT ) && $user->exists() ) {
            throw new NotFoundException( USER, $userId );
        }

        if ( ! $group->exists() ) {
            throw new NotFoundException( GROUP, $name );
        }

        if ( $user->exists() && ! $user->isMemberOf( $group ) ) {
            throw new NoMemberException( $user, $group );
        }

        $this->data[ 'year' ] = $year;
        $this->data[ 'user' ] = $user;
        $this->data[ 'group' ] = $group;
        $this->data[ 'questions' ] = $app[ 'questions' ];
        $this->data[ 'mode' ] = ( $user->exists() ) ? USER : GROUP;
        $this->data[ 'answers' ] = Answer::findByGroup( $group, $year );
        $this->data[ 'emissions' ] = ( $user->exists() )
            ? $user->getEmissions( $group, $year )
            : $group->getEmissions( $year, FALSE );
        $this->data[ 'offset_amount' ] = ( $user->exists() )
            ? $user->getOffsetAmount( $group, $year )
            : $group->getOffsetAmount( $year );

        return $this->respond( SUCCESS );
    }

    public function saveAnswer( $name, $year, $userId = NULL, Request $request, Application $app )
    {
        // @TODO USE FACTORY
        // THIS SHOULD HAVE PASSED A GROUP OBJECT INSTEAD OF NAME
        $user = new User( $userId );
        $post = $request->request->all();
        $group = Group::loadByName( $name );
        $answerVal = get( $post, 'answer' );
        $selectVal = get( $post, 'select' );

        if ( ! valid( $userId, INT ) && $user->exists() ) {
            throw new NotFoundException( USER, $userId );
        }

        if ( ! $group->exists() ) {
            throw new NotFoundException( GROUP, $name );
        }

        if ( $user->exists() && ! $user->isMemberOf( $group ) ) {
            throw new NoMemberException( $user, $group );
        }

        if ( ! valid( $answerVal, STRING ) ) {
            return $this->respond( INFO, "Please enter a value for that answer." );
        }

        // Save the answer to the database
        $answer = new Answer([
            'year' => $year,
            'group_id' => $group->id,
            'question_id' => get( $post, 'question_id' ),
            'user_id' => ( $user->exists() ) ? $user->id : NULL
        ]);
        $questions = new Questions( $app[ 'questions' ] );
        $questions->saveAnswer( $answer, $answerVal, $selectVal );
        // get answers
        //$questions->writeEmissions( $group, $user );

        $this->data[ 'answers' ] = Answer::findByGroup( $group, $year );
        $this->data[ 'emissions' ] = ( $user->exists() )
            ? $user->getEmissions( $group, $year )
            : $group->getEmissions( $year, FALSE );
        $this->data[ 'offset_amount' ] = ( $user->exists() )
            ? $user->getOffsetAmount( $group, $year )
            : $group->getOffsetAmount( $year );

        return $this->respond( SUCCESS );
    }

    /**
     * Master response method. This sends back a response in the
     * same format to the client.
     * @param const $status
     * @param string|array $message
     * @param int $code
     * @return JsonResponse
     */
    protected function respond( $status = NULL, $message = NULL, $code = NULL )
    {
        if ( ! is_null( $status ) ) {
            $this->status = $status;
        }

        if ( ! is_null( $code ) ) {
            $this->code = $code;
        }

        if ( ! is_null( $message ) ) {
            if ( is_array( $message ) ) {
                $this->messages = $message;
            }
            else {
                $this->message = $message;
                $this->messages = [[
                    'type' => $status,
                    'message' => $message
                ]];
            }
        }

        return new JsonResponse([
            'code' => $this->code,
            'data' => $this->data,
            'status' => $this->status,
            'message' => $this->message,
            'messages' => $this->messages
        ]);
    }
}