<?php

namespace App;

use Silex\Application
  , Symfony\Component\HttpFoundation\Request
  , Symfony\Component\HttpFoundation\JsonResponse;

class Controller
{
    protected $data = [];
    protected $code = 200;
    protected $message = "";
    protected $messages = [];
    protected $status = SUCCESS;

    public function ping( Request $request, Application $app )
    {
        return $this->respond( SUCCESS, "Pong" );
    }

    public function login( Request $request, Application $app )
    {
        // Verify the email and password. Create a new session
        // and save the cookie.
        return $this->respond( SUCCESS, "You have logged in" );
    }

    public function logout()
    {

    }

    public function dashboard() {}

    public function saveUser() {}

    public function removeUser() {}

    public function saveGroup() {}

    public function removeGroup() {}

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