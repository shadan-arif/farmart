<?php

namespace Botble\RezgoConnector\Services;

use Botble\Ecommerce\Models\Order;
use Botble\Setting\Facades\Setting;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class RezgoApiService
{
    protected Client $client;

    protected string $baseUrl = 'https://api.rezgo.com';

    public function __construct()
    {
        $this->client = new Client([
            'timeout'     => 30,
            'verify'      => true,
        ]);
    }

    /**
     * Retrieve the Rezgo CID (transcode) from settings.
     */
    public function getCid(): string
    {
        return (string) Setting::get('rezgo_cid', '');
    }

    /**
     * Retrieve and decrypt the Rezgo API key from settings.
     */
    public function getApiKey(): string
    {
        $encrypted = Setting::get('rezgo_api_key', '');

        if (empty($encrypted)) {
            return '';
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Exception) {
            return '';
        }
    }

    /**
     * Check if the plugin is enabled and credentials are set.
     */
    public function isEnabled(): bool
    {
        return Setting::get('rezgo_enabled', false)
            && ! empty($this->getCid())
            && ! empty($this->getApiKey());
    }

    /**
     * Test the connection by calling the "company" instruction.
     */
    public function testConnection(): array
    {
        $cid = $this->getCid();
        $key = $this->getApiKey();

        if (empty($cid) || empty($key)) {
            return ['success' => false, 'message' => 'Rezgo credentials are not configured.'];
        }

        try {
            $response = $this->client->get("{$this->baseUrl}/json", [
                'query' => [
                    'transcode' => $cid,
                    'key'       => $key,
                    'i'         => 'company',
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            $data = $body[0] ?? $body;

            if (is_array($data) && isset($data['company_name'])) {
                return [
                    'success' => true,
                    'message' => 'Connected successfully to: ' . $data['company_name'],
                    'data'    => $data,
                ];
            }

            $errorMsg = is_string($data) ? $data : 'Unexpected Rezgo response.';
            return ['success' => false, 'message' => $errorMsg, 'data' => $body];
        } catch (GuzzleException $e) {
            $this->writeLog('TEST_CONNECTION_ERROR', [], $e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Commit a booking to Rezgo for a specific order item.
     *
     * @param  Order  $order
     * @param  string $rezgoUid    The Rezgo tour UID for this product
     * @param  string $bookDate    Booking date (YYYY-MM-DD)
     * @param  int    $adultNum    Number of adult tickets
     * @param  string $refId       The local Farmart order reference
     * @return array{success: bool, trans_num: ?string, message: string}
     */
    public function commitBooking(
        Order $order,
        string $rezgoUid,
        string $bookDate,
        int $adultNum = 1,
        string $refId = ''
    ): array {
        $cid       = $this->getCid();
        $key       = $this->getApiKey();
        $address   = $order->shippingAddress ?? $order->address ?? null;
        $customer  = $order->user ?? null;

        $firstName = $address?->name ? explode(' ', $address->name)[0] : ($customer?->name ? explode(' ', $customer->name)[0] : 'Guest');
        $lastName  = $address?->name ? (explode(' ', $address->name)[1] ?? 'Customer') : ($customer?->name ? (explode(' ', $customer->name)[1] ?? 'Customer') : 'Customer');
        $email     = $address?->email ?? $order->user?->email ?? 'noreply@example.com';
        $phone     = $address?->phone ?? '';
        $city      = $address?->city ?? '';
        $state     = $address?->state ?? '';
        $country   = $address?->country ?? 'US';
        $postal    = $address?->zip_code ?? '';
        $street    = $address?->address ?? '';
        $ip        = request()->ip() ?? '127.0.0.1';

        if (empty($refId)) {
            $refId = 'farmart-order-' . $order->id;
        }

        $xmlBody = $this->buildCommitXml(
            cid: $cid,
            key: $key,
            rezgoUid: $rezgoUid,
            bookDate: $bookDate,
            adultNum: $adultNum,
            firstName: $firstName,
            lastName: $lastName,
            email: $email,
            phone: $phone,
            city: $city,
            state: $state,
            country: $country,
            postalCode: $postal,
            address1: $street,
            ip: $ip,
            refId: $refId,
        );

        $this->writeLog('COMMIT_REQUEST', [
            'order_id'  => $order->id,
            'rezgo_uid' => $rezgoUid,
            'book_date' => $bookDate,
            'adults'    => $adultNum,
        ], $xmlBody);

        try {
            $response = $this->client->post("{$this->baseUrl}/xml", [
                'body'    => $xmlBody,
                'headers' => [
                    'Content-Type' => 'application/xml',
                    'Accept'       => 'application/xml',
                ],
            ]);

            $rawXml = $response->getBody()->getContents();
            $this->writeLog('COMMIT_RESPONSE', ['order_id' => $order->id], $rawXml);

            $parsed = $this->parseXmlResponse($rawXml);

            if (isset($parsed['error'])) {
                return [
                    'success'   => false,
                    'trans_num' => null,
                    'message'   => 'Rezgo API error: ' . ($parsed['error'] ?? 'Unknown error'),
                ];
            }

            $transNum = $parsed['trans_num'] ?? null;

            return [
                'success'   => ! empty($transNum),
                'trans_num' => $transNum,
                'message'   => ! empty($transNum) ? 'Booking committed successfully.' : 'No trans_num in response.',
            ];
        } catch (GuzzleException $e) {
            $this->writeLog('COMMIT_ERROR', ['order_id' => $order->id], $e->getMessage());

            return [
                'success'   => false,
                'trans_num' => null,
                'message'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Build the XML commit payload per Rezgo API spec.
     */
    protected function buildCommitXml(
        string $cid,
        string $key,
        string $rezgoUid,
        string $bookDate,
        int    $adultNum,
        string $firstName,
        string $lastName,
        string $email,
        string $phone,
        string $city,
        string $state,
        string $country,
        string $postalCode,
        string $address1,
        string $ip,
        string $refId,
    ): string {
        $adultNum = max(1, $adultNum);

        // Build tour_group entries for each adult
        $tourGroup = '';
        for ($i = 1; $i <= $adultNum; $i++) {
            $tourGroup .= sprintf(
                '<adult num="%d"><first_name>%s</first_name><last_name>%s</last_name></adult>',
                $i,
                htmlspecialchars($firstName),
                htmlspecialchars($lastName),
            );
        }

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<request>
    <transcode>{$cid}</transcode>
    <key>{$key}</key>
    <instruction>commit</instruction>
    <booking>
        <date>{$bookDate}</date>
        <book>{$rezgoUid}</book>
        <adult_num>{$adultNum}</adult_num>
        <child_num>0</child_num>
        <senior_num>0</senior_num>
        <tour_group>
            {$tourGroup}
        </tour_group>
    </booking>
    <payment>
        <tour_first_name>{$firstName}</tour_first_name>
        <tour_last_name>{$lastName}</tour_last_name>
        <tour_address_1>{$address1}</tour_address_1>
        <tour_city>{$city}</tour_city>
        <tour_stateprov>{$state}</tour_stateprov>
        <tour_country>{$country}</tour_country>
        <tour_postal_code>{$postalCode}</tour_postal_code>
        <tour_phone_number>{$phone}</tour_phone_number>
        <tour_email_address>{$email}</tour_email_address>
        <payment_method>Cash</payment_method>
        <agree_terms>1</agree_terms>
        <status>1</status>
        <ip>{$ip}</ip>
        <refid>{$refId}</refid>
    </payment>
</request>
XML;
    }

    /**
     * Parse the Rezgo XML response into an associative array.
     */
    protected function parseXmlResponse(string $xml): array
    {
        try {
            $parsed = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

            if ($parsed === false) {
                return ['error' => 'Could not parse XML response.'];
            }

            return json_decode(json_encode($parsed), true) ?? [];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Write a structured entry to the dedicated Rezgo sync log.
     */
    protected function writeLog(string $event, array $context, string $detail = ''): void
    {
        $logger = Log::build([
            'driver' => 'daily',
            'path'   => storage_path('logs/rezgo-sync.log'),
            'days'   => 14,
        ]);

        $logger->info("[REZGO] {$event}", array_merge($context, ['detail' => $detail]));
    }
}
