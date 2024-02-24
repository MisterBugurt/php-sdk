<?php

declare(strict_types=1);

namespace SpExpress\Sdk\TransportClient\Curl;

use SpExpress\Sdk\TransportClient\TransportClient;
use SpExpress\Sdk\TransportClient\TransportClientResponse;
use SpExpress\Sdk\TransportClient\TransportRequestException;

class HttpClient implements TransportClient
{
    protected $login;
    protected $apiToken;

    public function authorize(string $login = null, string $apiToken = null): TransportClient
    {
        $this->login = $login;
        $this->apiToken = $apiToken;

        return $this;
    }

    public function get(string $url, ?array $payload): TransportClientResponse
    {
        if ($curl = curl_init()) {
            $url = ($url . '?' . http_build_query($payload));

            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl, CURLOPT_USERPWD, $this->login . ':' . $this->apiToken);
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-type:application/json']);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);


            $response = curl_exec($curl);

            $httpStatus = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            $curlErrorCode = curl_errno($curl);
            $curlError = curl_error($curl);

            if ($curlError || $curlErrorCode) {
                $errorMessage = "Failed curl request. Curl error {$curlErrorCode}";

                if ($curlError) {
                    $errorMessage .= ": {$curlError}";
                }

                $errorMessage .= '.';

                throw new TransportRequestException($errorMessage);
            }

            return new TransportClientResponse($httpStatus, $response);
        }
    }

    public function post(string $url, ?array $payload): TransportClientResponse
    {
        if ($curl = curl_init()) {
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl, CURLOPT_USERPWD, $this->login . ':' . $this->apiToken);
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-type:application/json']);

            $apiVersionHeader = 'X-API-Version: ' . $this->readVersionFile();
            curl_setopt($curl, CURLOPT_HTTPHEADER, [$apiVersionHeader]);

            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            $response = curl_exec($curl);

            $httpStatus = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            $curlErrorCode = curl_errno($curl);
            $curlError = curl_error($curl);

            if ($curlError || $curlErrorCode) {
                $errorMessage = "Failed curl request. Curl error {$curlErrorCode}";

                if ($curlError) {
                    $errorMessage .= ": {$curlError}";
                }

                $errorMessage .= '.';

                throw new TransportRequestException($errorMessage);
            }

            return new TransportClientResponse($httpStatus, $response);
        }
    }

    private function readVersionFile()
    {
        // Define the path to the .version file
        $versionFilePath = __DIR__ . '/../../../.version';

        // Check if the file exists
        if (!file_exists($versionFilePath)) {
            // Handle the error if the file does not exist
            throw new TransportRequestException("The .version file does not exist.");
        }

        // Read the contents of the file
        $version = file_get_contents($versionFilePath);

        // Check if the file was read successfully
        if ($version === false) {
            // Handle the error if the file could not be read
            throw new TransportRequestException("Could not read the .version file.");
        }

        // Trim any whitespace from the beginning and end of the version string
        $version = trim($version);

        // Validate the version string
        if (!$this->isSemanticVersion($version)) {
            // Handle the error if the file is not valid
            throw new TransportRequestException("The version in the .version file is not a valid Semantic Versioning compliant version.");
        }

        return $version;
    }

    /**
     * Checks if the given string is a version that conforms to Semantic Versioning.
     */
    private function isSemanticVersion(string $version): bool
    {
        $pattern = '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-((?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+([0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/';

        if (preg_match($pattern, $version)) {
            return true;
        }

        return false;
    }
}
