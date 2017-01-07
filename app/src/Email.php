<?php

namespace App;

use Exception
  , App\Entities\User
  , Postmark\PostmarkClient
  , Postmark\Models\PostmarkException
  , App\Exceptions\Email as EmailException;

class Email
{
    private $apiKey;
    private $fromAddress = "no-reply@earthboost.org";

    const SINGLE_BREAK = "<br>";
    const DOUBLE_BREAK = "<br><br>";

    public function __construct( $apiKey )
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Sends an email with a login token to the user.
     * @param User $user
     * @param string $path
     * @param string $token
     */
    public function sendLoginToken( User $user, $path, $token )
    {
        $url = $path ."?token=". $token;
        $tokenLink = sprintf( '<a href="%s">%s</a>', $url, $url );
        $message = sprintf(
            "Hi %s,%sFollow this link to log in to EarthBoost:".
            "%s%s%sYou can paste it into your browser's URL ".
            "bar too. This link will be valid for 72 hours.%s".
            "--\nEarthBoost",
            $user->name ?: "there",
            self::DOUBLE_BREAK,
            self::DOUBLE_BREAK,
            $tokenLink,
            self::DOUBLE_BREAK,
            self::DOUBLE_BREAK );

        $this->send(
            $user->email,
            "[Action Required] Login to EarthBoost",
            $message );
    }

    /**
     * Sends an email.
     * @param string $toAddress
     * @param string $subject
     * @param string $message
     * @throws EmailException
     */
    public function send( $toAddress, $subject, $message )
    {
        $client = new PostmarkClient( $this->apiKey );

        try {
            $sendResult = $client->sendEmail(
                $this->fromAddress,
                $toAddress,
                $subject,
                $message );
        }
        catch ( PostmarkException $e ) {
            throw new EmailException(
                "Postmark failed with HTTP code ".
                $e->httpStatusCode .": ". $e->message .
                " [ ". $e->postmarkApiErrorCode ."]" );
        }
        catch ( Exception $e ) {
            throw new EmailException( $e->getMessage() );
        }
    }
}