<?php

declare(strict_types=1);

namespace App\Connectors\Adapters;

use Nexus\Connector\Contracts\SmsServiceConnectorInterface;
use Nexus\Connector\Exceptions\ConnectionException;
use Twilio\Rest\Client as TwilioClient;
use Twilio\Exceptions\TwilioException;

/**
 * Twilio SMS adapter.
 *
 * This adapter translates the generic SmsServiceConnectorInterface
 * into Twilio-specific API calls.
 */
final class TwilioSmsAdapter implements SmsServiceConnectorInterface
{
    private TwilioClient $client;

    /**
     * @param string $accountSid Twilio account SID
     * @param string $authToken Twilio auth token
     * @param string $fromNumber Default sender phone number
     */
    public function __construct(
        private readonly string $accountSid,
        private readonly string $authToken,
        private readonly string $fromNumber,
    ) {
        $this->client = new TwilioClient($this->accountSid, $this->authToken);
    }

    /**
     * {@inheritDoc}
     */
    public function send(string $phoneNumber, string $message, array $options = []): string
    {
        try {
            $params = [
                'from' => $options['sender_id'] ?? $this->fromNumber,
                'body' => $message,
            ];

            // Add status callback if provided
            if (isset($options['status_callback'])) {
                $params['statusCallback'] = $options['status_callback'];
            }

            // Add media URLs for MMS
            if (isset($options['media_urls'])) {
                $params['mediaUrl'] = $options['media_urls'];
            }

            $twilioMessage = $this->client->messages->create(
                $phoneNumber,
                $params
            );

            return $twilioMessage->sid;

        } catch (TwilioException $e) {
            throw ConnectionException::requestFailed(
                message: "Twilio SMS send failed: {$e->getMessage()}",
                httpStatusCode: $e->getCode(),
                previous: $e
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function sendBulk(array $messages, array $options = []): array
    {
        $sent = 0;
        $failed = 0;
        $messageIds = [];

        foreach ($messages as $sms) {
            try {
                $messageId = $this->send($sms['phone'], $sms['message'], $options);
                $messageIds[] = $messageId;
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                $messageIds[] = null;
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'message_ids' => $messageIds,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function validatePhoneNumber(string $phoneNumber): array
    {
        try {
            $lookup = $this->client->lookups->v1->phoneNumbers($phoneNumber)
                ->fetch(['type' => ['carrier']]);

            return [
                'valid' => true,
                'country_code' => $lookup->countryCode,
                'carrier' => $lookup->carrier['name'] ?? null,
            ];

        } catch (TwilioException $e) {
            return [
                'valid' => false,
                'country_code' => null,
                'carrier' => null,
            ];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function checkBalance(): array
    {
        try {
            $account = $this->client->api->v2010->accounts($this->accountSid)
                ->fetch();

            return [
                'balance' => (float) $account->balance,
                'currency' => $account->currency ?? 'USD',
            ];

        } catch (TwilioException $e) {
            throw ConnectionException::requestFailed(
                message: "Twilio balance check failed: {$e->getMessage()}",
                httpStatusCode: $e->getCode(),
                previous: $e
            );
        }
    }
}
