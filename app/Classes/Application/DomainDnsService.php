<?php

namespace App\Classes\Application;

use App\Classes\Application\Contracts\DomainDnsServiceInterface;
use App\Classes\Application\Contracts\PhoneUtilsInterface;
use App\Classes\Application\Exceptions\NamecheapDomainDnsException;
use App\Classes\NamecheapWrapper\Contracts\ApiWrapperFactoryServiceInterface;
use Exception;
use Illuminate\Support\Facades\Log;

class DomainDnsService implements DomainDnsServiceInterface
{
    private ApiWrapperFactoryServiceInterface $apiFactory;
    private PhoneUtilsInterface $phoneUtils;

    public function __construct(ApiWrapperFactoryServiceInterface $apiFactory, PhoneUtilsInterface $phoneUtils)
    {
        $this->apiFactory = $apiFactory;
        $this->phoneUtils = $phoneUtils;
    }

    public function getDnsRecords(string $userName, string $apiKey, string $domain): array
    {
        try {
            $api = $this->apiFactory->getNewInstance($userName, $apiKey, $userName);
            $response = $api->getDomainsDns()->getHosts($this->getDomainSLD($domain), $this->getDomainTLD($domain));
            $decodedResponse = json_decode($response, true);
            if (isset($decodedResponse['ApiResponse']['CommandResponse']['DomainDNSGetHostsResult']['host'])) {
                $hosts = $decodedResponse['ApiResponse']['CommandResponse']['DomainDNSGetHostsResult']['host'];

                // If one record, we make array from it
                if (isset($hosts['_HostId'])) {
                    $hosts = [$hosts];
                }

                $result = collect($hosts)
                    ->map(function ($record) {
                        return [
                            'RecordType' => $record['_Type'],
                            'HostName' => $record['_Name'],
                            'Address' => $record['_Address'],
                            'MXPref' => $record['_MXPref'] ?? null,
                            'TTL' => $record['_TTL'],
                        ];
                    })
                    ->toArray();

                return $result;
            }
            return [];
        } catch (Exception $e) {
            Log::error('Error loading DNS records', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new NamecheapDomainDnsException($e->getMessage());
        }
    }

    public function setDnsRecords(string $userName, string $apiKey, string $domain, array $dnsRecords): array
    {
        $hostNames = [];
        $recordTypes = [];
        $addresses = [];
        $mXPrefs = [];
        $ttls = [];

        // We check whether there is an embedded key 'dnsRecords'
        $records = $dnsRecords['dnsRecords'] ?? $dnsRecords;

        $counter = 1;
        foreach ($records as $record) {

            if (!isset($record['HostName']) || !isset($record['RecordType']) || !isset($record['Address']) || !isset($record['TTL'])) {
                Log::error('Invalid record structure', ['record' => $record]);
                throw new NamecheapDomainDnsException("Record at index {$counter} is missing required fields");
            }

            $hostNames["HostName{$counter}"] = $record['HostName'];
            $recordTypes["RecordType{$counter}"] = $record['RecordType'];
            $addresses["Address{$counter}"] = $record['Address'];
            $mXPrefs["MXPref{$counter}"] = $record['MXPref'] ?? '10';
            $ttls["TTL{$counter}"] = $record['TTL'];

            $counter++;
        }

        try {

            $api = $this->apiFactory->getNewInstance($userName, $apiKey, $userName);

            $response = $api->getDomainsDns()->setHosts(
                $this->getDomainSLD($domain),
                $this->getDomainTLD($domain),
                $hostNames,
                $recordTypes,
                $addresses,
                $mXPrefs,
                null, // EmailType
                $ttls
            );

            $decodedResponse = json_decode($response, true);

            if (
                isset($decodedResponse['ApiResponse']['CommandResponse']['DomainDNSSetHostsResult']['_IsSuccess'])
                && $decodedResponse['ApiResponse']['CommandResponse']['DomainDNSSetHostsResult']['_IsSuccess'] === 'true'
            ) {
                return $decodedResponse['ApiResponse']['CommandResponse'];
            } else {
                $errorMessage = $decodedResponse['ApiResponse']['Errors']['Error'][0] ?? 'Unknown error';
                Log::error('Failed to update DNS records', ['error' => $errorMessage]);
                throw new NamecheapDomainDnsException('Failed to update DNS records' . ': ' . $errorMessage);
            }
        } catch (\Exception $ex) {
            Log::error('Error saving DNS records', [
                'error' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString(),
            ]);
            throw new NamecheapDomainDnsException('Failed to update DNS records' . ': ' . $ex->getMessage());
        }
    }

    private function getDomainSLD(string $domain): string
    {
        return explode('.', $domain)[0];
    }

    private function getDomainTLD(string $domain): string
    {
        return implode('.', array_slice(explode('.', $domain), 1));
    }
}
