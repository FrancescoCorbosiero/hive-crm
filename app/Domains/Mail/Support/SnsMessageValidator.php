<?php

declare(strict_types=1);

namespace App\Domains\Mail\Support;

use Aws\Sns\Message;
use Aws\Sns\MessageValidator;

/**
 * Thin wrapper around aws/aws-php-sns-message-validator.
 *
 * Exists so the controller can depend on a single bound interface and
 * tests can swap in a fake without monkey-patching the AWS SDK.
 */
class SnsMessageValidator
{
    public function __construct(
        private readonly ?MessageValidator $validator = null,
    ) {}

    /**
     * Verify and parse an SNS notification body. Throws on invalid
     * signature or malformed payload.
     */
    public function validate(string $rawBody): Message
    {
        $message = new Message(json_decode($rawBody, true) ?: []);

        ($this->validator ?? new MessageValidator())->validate($message);

        return $message;
    }
}
