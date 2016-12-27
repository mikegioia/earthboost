<?php

namespace App\Libraries;

use Exception
  , Postmark\PostmarkClient
  , Postmark\Models\PostmarkException
  , App\Exceptions\Email as EmailException;

class Email
{
    private $apiKey;
    private $fromAddress = "no-reply@earthboost.org";

    public function __construct( $apiKey )
    {
        $this->apiKey = $apiKey;
    }

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