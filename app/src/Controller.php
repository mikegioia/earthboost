<?php

namespace App;

use Silex\Application
  , App\Entities\Group
  , Symfony\Component\HttpFoundation\Request
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

    public function login( Request $request )
    {
        // Verify the email and password. Create a new session
        // and save the cookie.
        return $this->respond( SUCCESS, "You have logged in" );
    }

    public function logout()
    {

    }

    public function dashboard()
    {
        exit('dash');
    }

    /**
     * Prepare all of the dashboard data for a group.
     */
    public function group( $name )
    {
        $group = Group::loadByName( $name );

        if ( ! $group->exists() ) {
            throw new NotFoundException( GROUP, $name );
        }

        // Prepare all of the statistics and return them
        $this->data[ 'staff' ] = [];
        $this->data[ 'emissions' ] = 240;
        $this->data[ 'offset_amount' ] = 2456.01;

        return $this->respond( SUCCESS );
    }

    /**
     * Master response method. This sends back a response in the
     * same format to the client.
     * @param const $status
     * @param string $message
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