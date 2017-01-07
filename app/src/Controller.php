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

    /**
     * Checks if the email exists and if so, sends an email to
     * the user with a link to log in.
     */
    public function login( Request $request, Application $app )
    {
        $email = $request->request->get( 'email' );
        $user = User::getByEmail( $email );

        if ( ! $user->exists() ) {
            throw new NotFoundException( USER, $email );
        }

        // Send the email
        $token = $app[ 'session' ]->createLoginToken( $user );
        $app[ 'email' ]->sendLoginToken(
            $user,
            $app[ 'config' ]->app_path ."/authorize",
            $token );

        // App will redirect to this notice page
        $this->data[ 'url' ] = '/check-email';

        return $this->respond( SUCCESS, "", 302 );
    }

    /**
     * Tries to log in a user based off their token.
     */
    public function authorize( Request $request, Application $app )
    {
        $token = $request->request->get( 'token' );
        $app[ 'session' ]->createFromToken( $token );

        // App will redirect to dashboard
        $groups = $app[ 'session' ]->getUser()->getGroups();
        $this->data[ 'url' ] = ( count( $groups ) > 1 )
            ? '/'
            : '/' + $groups[ 0 ]->name;

        return $this->respond( SUCCESS, "Welcome back :)", 302 );
    }

    public function logout( Application $app )
    {
        $app[ 'session' ]->destroy();

        return $this->respond( SUCCESS, "You have been logged out." );
    }

    /**
     * Records user info and redirects them to success message.
     */
    public function signup( Request $request )
    {
    }

    public function dashboard( Application $app )
    {
        $user = $app[ 'session' ]->getUser();
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

        $year = ( $year ) ?: date( "Y" ) - (date( "m" ) <= 3 ? 1 : 0);
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
        $this->data[ 'is_admin' ] = $app[ 'auth' ]->isAdmin()
            || $user->getMember( $group, $year )->isAdmin();
        $this->data[ 'members' ] = $group->getMembers( $year, TRUE );
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

        if ( ! is_array( $answerVal ) && ! valid( $answerVal, STRING ) ) {
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
        // If all questions are answered, remove the is_standard flag
        $questions->writeEmissions( $group, $user, $year, TRUE );

        // Return an updated copy of the answers and emissions data
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
     * Admin panel.
     */
    public function admin()
    {
        exit('admin!');
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