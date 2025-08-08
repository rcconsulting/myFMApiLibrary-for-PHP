<?php

namespace RCConsulting\FileMakerApi;

use RCConsulting\FileMakerApi\Exception\Exception;
use RCConsulting\FileMakerApi\HttpClientType;
use RCConsulting\FileMakerApi\DapiVersion;
use ValueError;

/**
 * Class DataApi
 * @package RCConsulting\DataApi
 */
final class DataApi implements DataApiInterface {

    const int FILEMAKER_NO_RECORDS = 401;
    const int FILEMAKER_API_TOKEN_EXPIRED = 952;

    const string SCRIPT_PREREQUEST = 'prerequest';
    const string SCRIPT_PRESORT = 'presort';
    const string SCRIPT_POSTREQUEST = 'postrequest';
    const int DATE_DEFAULT = 0;
    const int DATE_FILELOCALE = 1;
    const int DATE_ISO8601 = 2;

    protected HttpClientInterface $ClientRequest;
    protected ?string $apiDatabase = null;
    protected ?string $apiToken = null;
    protected ?string $apiTokenDate = null;
    protected bool $convertToAssoc = True;
    protected ?string $dapiUserName = null;
    protected ?string $dapiUserPass = null;
    protected ?string $oAuthRequestId = null;
    protected ?string $oAuthIdentifier = null;
    protected DapiVersion $DAPIVersion = DapiVersion::V1;
    protected string $DAPIVersionString = 'v1';
    protected bool $hasToken = False;
    protected bool $returnResponseObject = False;


    /**
     * DataApi constructor
     *
     * @param string $apiUrl
     * @param string $apiDatabase
     * @param string|null $apiUser
     * @param string|null $apiPassword
     * @param bool $sslVerify
     * @param bool $forceLegacyHTTP
     * @param bool $returnResponseObject
     * @param HttpClientType|string $httpClient HTTP client implementation to use (HttpClientType enum or string 'curl'/'guzzle')
     * @param DapiVersion|string $dapiVersion
     * @throws Exception
     */
    public function __construct(string $apiUrl, string $apiDatabase, ?string $apiUser = null, ?string $apiPassword = null, bool $sslVerify = True, bool $forceLegacyHTTP = False, bool $returnResponseObject = False, HttpClientType|string $httpClient = HttpClientType::CURL, DapiVersion|string $dapiVersion = DapiVersion::V1 )
    {
        $this->apiDatabase = $this->prepareURLpart($apiDatabase);
        $this->ClientRequest = $this->createHttpClient($httpClient, $apiUrl, $sslVerify, $forceLegacyHTTP);
        $this->returnResponseObject = $returnResponseObject;
        $this->DAPIVersion = is_string($dapiVersion) ? DapiVersion::fromString($dapiVersion) : $dapiVersion;

        if (!is_null($apiUser)) {
            $this->login($apiUser, $apiPassword);
        }
    }

    /**
     * Create HTTP client instance based on the specified type
     *
     * @param HttpClientType|string $httpClient The HTTP client type (enum or string 'curl'/'guzzle')
     * @param string $apiUrl The base URL for the FileMaker Data API
     * @param bool $sslVerify Whether to verify SSL certificates
     * @param bool $forceLegacyHTTP Whether to force HTTP/1.1 instead of HTTP/2
     *
     * @return HttpClientInterface
     * @throws Exception When an invalid HTTP client type is specified
     */
    private function createHttpClient(HttpClientType|string $httpClient, string $apiUrl, bool $sslVerify, bool $forceLegacyHTTP): HttpClientInterface
    {
        if (is_string($httpClient)) {
            $httpClient = HttpClientType::fromString($httpClient);
        }

        return match ($httpClient) {
            HttpClientType::CURL => new CurlClient($apiUrl, $sslVerify, $forceLegacyHTTP),
            HttpClientType::GUZZLE => new GuzzleClient($apiUrl, $sslVerify, $forceLegacyHTTP),
        };
    }

    /**
     * Login to FileMaker API
     *
     * @param string $apiUsername
     * @param string $apiPassword
     *
     * @return $this
     * @throws Exception
     */
    public function login(string $apiUsername, string $apiPassword): DataApi
    {
        $response = $this->ClientRequest->request(
            'POST',
            "/$this->DAPIVersionString/databases/$this->apiDatabase/sessions",
            [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode("$apiUsername:$apiPassword"),
                ],
                'json' => [],
            ]
        );
        $this->setApiToken($response->getRawResponse()['token']);
        $this->storeCredentials($apiUsername, $apiPassword);

        return $this;
    }

    /**
     * @param string $oAuthRequestId
     * @param string $oAuthIdentifier
     *
     * @return $this
     * @throws Exception
     */
    public function loginOauth(string $oAuthRequestId, string $oAuthIdentifier): DataApi
    {
        $response = $this->ClientRequest->request(
            'POST',
            "/$this->DAPIVersionString/databases/$this->apiDatabase/sessions",
            [
                'headers' => [
                    'X-FM-Data-Login-Type' => 'oauth',
                    'X-FM-Data-OAuth-Request-Id' => $oAuthRequestId,
                    'X-FM-Data-OAuth-Identifier' => $oAuthIdentifier,
                ],
                'json' => [],
            ]
        );
        $this->setApiToken($response->getRawResponse()['token']);
        $this->storeOAuth($oAuthRequestId, $oAuthIdentifier);

        return $this;
    }

    /**
     *  Close the connection with FileMaker Server API
     * @throws Exception
     */
    public function logout(): DataApi
    {
        $this->ClientRequest->request(
            'DELETE',
            "/$this->DAPIVersionString/databases/$this->apiDatabase/sessions/$this->apiToken",
            []
        );
        $this->apiToken = null;
        $this->apiTokenDate = null;
        $this->hasToken = False;

        return $this;
    }

    // BASIC RECORD OPERATIONS

    /**
     * @param string $layout
     * @param array $data
     * @param array $scripts
     * @param array $portalData
     *
     * @return mixed
     * @throws Exception
     */
    public function createRecord(string $layout, array $data, array $scripts = [], array $portalData = []): mixed
    {
        $layout = $this->prepareURLpart($layout);
        $jsonOptions = [
            'fieldData' => array_map('\strval', $data)
        ];
        // TODO: ? encodeFieldData()?
        // TODO: ? encodePortalData()?
        if (!empty($portalData)) {
            $jsonOptions['portalData'] = $portalData;
        }

        $response = $this->ClientRequest->request(
            'POST',
            "/$this->DAPIVersionString/databases/$this->apiDatabase/layouts/$layout/records",
            [
                'headers' => $this->getDefaultHeaders(),
                'json' => array_merge(
                    $jsonOptions,
                    $this->prepareScriptOptions($scripts)
                ),
            ]
        );
        if ($this->returnResponseObject) {
            return $response;
        } else {
            return $response->getRecords()['response']['recordId'];
        }
    }

    /**
     * @param string $layout
     * @param        $recordId
     * @param array $data
     * @param null $lastModificationId
     * @param array $portalData
     * @param array $scripts
     *
     * @return mixed
     * @throws Exception
     */
    public function editRecord(string $layout, $recordId, array $data, $lastModificationId = null, array $portalData = [], array $scripts = []): mixed
    {
        $layout = $this->prepareURLpart($layout);
        $recordId = $this->prepareURLpart($recordId);
        $jsonOptions = [
            'fieldData' => array_map('\strval', $data),
        ];

        if (!is_null($lastModificationId)) {
            $jsonOptions['modId'] = $lastModificationId;
        }

        $response = $this->ClientRequest->request(
            'PATCH',
            "/$this->DAPIVersionString/databases/$this->apiDatabase/layouts/$layout/records/$recordId",
            [
                'headers' => $this->getDefaultHeaders(),
                'json' => array_merge(
                    $jsonOptions,
                    $this->prepareScriptOptions($scripts)
                ),
            ]
        );
        if ($this->returnResponseObject) {
            return $response;
        } else {
            return $response->getRawResponse()['modId'];
        }
    }

    /**
     * Duplicate an existing record
     *
     * @param string $layout
     * @param        $recordId
     * @param array $scripts
     *
     * @return mixed
     * @throws Exception
     */
    public function duplicateRecord(string $layout, $recordId, array $scripts = []): mixed
    {
        $layout = $this->prepareURLpart($layout);
        $recordId = $this->prepareURLpart($recordId);
        // Send curl request
        $response = $this->ClientRequest->request(
            'POST',
            "/$this->DAPIVersionString/databases/$this->apiDatabase/layouts/$layout/records/$recordId",
            [
                'headers' => $this->getDefaultHeaders(),
                'json' => array_merge(
                    $this->prepareScriptOptions($scripts)
                ),
            ]
        );
        if ($this->returnResponseObject) {
            return $response;
        } else {
            return $response->getRawResponse()['recordId'];
        }
    }

    /**
     * Delete record by id
     *
     * @param string $layout
     * @param        $recordId
     * @param array $scripts
     *
     * @throws Exception
     */
    public function deleteRecord(string $layout, $recordId, array $scripts = []): void
    {
        $layout = $this->prepareURLpart($layout);
        $recordId = $this->prepareURLpart($recordId);
        $this->ClientRequest->request(
            'DELETE',
            "/$this->DAPIVersionString/databases/$this->apiDatabase/layouts/$layout/records/$recordId",
            [
                'headers' => $this->getDefaultHeaders(),
                'json' => $this->prepareScriptOptions($scripts),
            ]
        );
    }

    /**
     * Get record detail
     *
     * @param string $layout
     * @param        $recordId
     * @param array $portalOptions
     * @param array $scripts
     * @param null $responseLayout
     * @param int|null $dateFormat
     * @return mixed
     * @throws Exception
     */
    public function getRecord(string $layout, $recordId, array $portalOptions = [], array $scripts = [], $responseLayout = null, ?int $dateFormat = self::DATE_DEFAULT): mixed
    {
        $layout = $this->prepareURLpart($layout);
        $recordId = $this->prepareURLpart($recordId);

        $queryParams = [];
        if (!empty($portalOptions)) {
            $queryParams['portal'] = $portalOptions['name'];

            if (isset($portalOptions['limit'])) {
                $queryParams['_limit.' . $queryParams['portal']] = $portalOptions['limit'];
            }
            if (isset($portalOptions['offset'])) {
                $queryParams['_offset.' . $queryParams['portal']] = $portalOptions['offset'];
            }
        }
        if (!is_null($responseLayout)) {
            $queryParams['layout.response'] = $responseLayout;
        }

        if (!is_null($dateFormat)) {
            $queryParams = array_merge(
                $queryParams,
                $this->prepareScriptOptions($scripts),
                $this->prepareDateFormat($dateFormat)
            );
        } else {
            $queryParams = array_merge(
                $queryParams,
                $this->prepareScriptOptions($scripts)
            );
        }

        $response = $this->ClientRequest->request(
            'GET',
            "/$this->DAPIVersionString/databases/$this->apiDatabase/layouts/$layout/records/$recordId",
            [
                'headers' => $this->getDefaultHeaders(),
                'query_params' => $queryParams,
            ]
        );
        if ($this->returnResponseObject) {
            return $response;
        } else {
            return $response->getRecords()[0];
        }
    }

    /**
     *  Get list of records
     *
     * @param string $layout
     * @param ?string $sort
     * @param ?int $offset
     * @param ?int $limit
     * @param array $portals
     * @param array $scripts
     * @param ?string $responseLayout
     * @param int $dateFormat
     * @return array|Response
     * @throws Exception
     */
    public function getRecords(string $layout, $sort = null, $offset = null, $limit = null, array $portals = [], array $scripts = [], ?string $responseLayout = null, ?int $dateFormat = self::DATE_DEFAULT): array|Response
    {
        $layout = $this->prepareURLpart($layout);
        $jsonOptions = [];

        if (!is_null($offset)) {
            $jsonOptions['_offset'] = intval($offset);
        }

        if (!is_null($limit)) {
            $jsonOptions['_limit'] = intval($limit);
        }

        if (!is_null($sort)) {
            $jsonOptions['_sort'] = (is_array($sort) ? json_encode($sort) : $sort);
        }

        if (!is_null($responseLayout)) {
            $jsonOptions['layout.response'] = $responseLayout;
        }

        if (!is_null($dateFormat)) {
            $jsonOptions = array_merge(
                $jsonOptions,
                $this->prepareScriptOptions($scripts),
                $this->preparePortalsOptions($portals),
                $this->prepareDateFormat($dateFormat)
            );
        } else {
            $jsonOptions = array_merge(
                $jsonOptions,
                $this->prepareScriptOptions($scripts),
                $this->preparePortalsOptions($portals)
            );
        }

        $response = $this->ClientRequest->request(
            'GET',
            "/$this->DAPIVersionString/databases/$this->apiDatabase/layouts/$layout/records",
            [
                'headers' => $this->getDefaultHeaders(),
                'query_params' => $jsonOptions,
            ]
        );
        if ($this->returnResponseObject) {
            return $response;
        } else {
            return $response->getRecords();
        }
    }

    /**
     *  Upload files into container
     *
     * @param string $layout
     * @param        $recordId
     * @param string $containerFieldName
     * @param        $containerFieldRepetition
     * @param string $filepath
     * @param string|null $filename
     *
     * @return True
     * @throws Exception
     */
    public function uploadToContainer(string $layout, $recordId, string $containerFieldName, $containerFieldRepetition, string $filepath, ?string $filename = null): bool
    {
        if (empty($filename)) {
            $filename = pathinfo($filepath, PATHINFO_FILENAME) . '.' . pathinfo($filepath, PATHINFO_EXTENSION);
        }
        $layout = $this->prepareURLpart($layout);
        $recordId = $this->prepareURLpart($recordId);
        $containerFieldName = $this->prepareURLpart($containerFieldName);
        $this->ClientRequest->request(
            'POST',
            "/$this->DAPIVersionString/databases/$this->apiDatabase/layouts/$layout/records/$recordId/containers/$containerFieldName/$containerFieldRepetition",
            [
                'headers' => array_merge(
                    $this->getDefaultHeaders(),
                    ['Content-Type' => 'multipart/form-data']
                ),
                'file' => [
                    'name' => $filename,
                    'path' => $filepath
                ]
            ]
        );
        return True;
    }

    /**
     * Find records
     *
     * @param string $layout
     * @param array $query
     * @param null $sort
     * @param null $offset
     * @param null $limit
     * @param array $portals
     * @param array $scripts
     * @param string|null $responseLayout
     * @param int $dateFormat
     * @return array|bool|Response
     * @throws Exception
     */
    public function findRecords(string $layout, array $query, $sort = null, $offset = null, $limit = null, array $portals = [], array $scripts = [], ?string $responseLayout = null, int $dateFormat = self::DATE_DEFAULT): array|bool|Response
    {
        $layout = $this->prepareURLpart($layout);

        if (!is_array($query)) {
            $preparedQuery = [$query];
        } else {
            $preparedQuery = [];
            foreach ($query as $queryItem) {
                if (!isset($queryItem['fields'])) {
                    break;
                }

                $item = [];
                foreach ($queryItem['fields'] as $field) {
                    $item[$field['fieldname']] = $field['fieldvalue'];
                }

                if (isset($queryItem['options']['omit'])) {
                    if ($queryItem['options']['omit'] || $queryItem['options']['omit'] == "true") {
                        $preparedQuery[] = array_merge($item, ['omit' => "true"]);
                    } else {
                        $preparedQuery[] = array_merge($item, ['omit' => "false"]);
                    }
                } else {
                    $preparedQuery[] = array_merge($item, ['omit' => "false"]);
                }
            }
        }
        $jsonOptions = [
            'query' => $preparedQuery,
        ];
        if (!is_null($offset)) {
            $jsonOptions['offset'] = intval($offset);
        }

        if (!is_null($limit)) {
            $jsonOptions['limit'] = intval($limit);
        }

        if (!is_null($responseLayout)) {
            $jsonOptions['layout.response'] = $responseLayout;
        }

        if (!is_null($sort)) {
            if (is_array($sort)) {
                $sortOptions = [
                    'sort' => $sort,
                ];
                $jsonOptions = array_merge($jsonOptions, $sortOptions);
            }
        }

        if (!is_null($dateFormat)) {
            $jsonOptions = array_merge(
                $jsonOptions,
                $this->prepareScriptOptions($scripts),
                $this->preparePortalsOptions($portals),
                $this->prepareDateFormat($dateFormat)
            );
        } else {
            $jsonOptions = array_merge(
                $jsonOptions,
                $this->prepareScriptOptions($scripts),
                $this->preparePortalsOptions($portals)
            );
        }

        try {
            $response = $this->ClientRequest->request(
                'POST',
                "/$this->DAPIVersionString/databases/$this->apiDatabase/layouts/$layout/_find",
                [
                    'headers' => $this->getDefaultHeaders(),
                    'json' => $jsonOptions,
                ]
            );
        } catch (Exception $e) {
            if ($err = $this->dAPIerrorHandler($e)) {
                return $err;
            } else {
                throw $e;
            }
        }
        if ($this->returnResponseObject) {
            return $response;
        } else {
            return $response->getRecords();
        }
    }

    /**
     * Execute script alone
     *
     * @param string $layout
     * @param string $scriptName
     * @param string|null $scriptParam
     *
     * @return string|true|Response
     * @throws Exception
     */
    public function executeScript(string $layout, string $scriptName, ?string $scriptParam = null): string|true|Response
    {
        $layout = $this->prepareURLpart($layout);
        $scriptName = $this->prepareURLpart($scriptName);

        if (!empty($scriptParam)) {
            // Prepare options
            $queryParams = [];
            // optional parameters
            $queryParams['script.param'] = $scriptParam;
            // set up parameters before sending to data api
            $options = [
                'headers' => $this->getDefaultHeaders(),
                'query_params' => array_merge(
                    $queryParams
                ),
            ];
        } else {
            $options = [
                'headers' => $this->getDefaultHeaders()
            ];
        }

        // Send curl request
        $response = $this->ClientRequest->request(
            'GET',
            "/$this->DAPIVersionString/databases/$this->apiDatabase/layouts/$layout/script/$scriptName",
            $options
        );
        if ($this->returnResponseObject) {
            return $response;
        } else {
            if ($response->getScriptError() == 0) {
                if (array_key_exists('scriptResult', $response->getRawResponse())) {
                    return $response->getScriptResult();
                } else {
                    return True;
                }
            } else {
                return $response->getScriptError();
            }
        }
    }

    /**
     * Define one or multiple global fields
     *
     * @param string $layout
     * @param array $globalFields
     *
     * @return array|Response
     * @throws Exception
     */
    public function setGlobalFields(string $layout, array $globalFields): array|Response
    {
        $layout = $this->prepareURLpart($layout);
        $response = $this->ClientRequest->request(
            'PATCH',
            "/$this->DAPIVersionString/databases/$this->apiDatabase/globals",
            [
                'headers' => $this->getDefaultHeaders(),
                'json' => [
                    'globalFields' => $globalFields,
                ],
            ]
        );
        if ($this->returnResponseObject) {
            return $response;
        } else {
            return $response->getBody();
        }
    }

    // UTILITY FUNCTIONS

    /**
     *  Set API token in request headers
     *
     * @return string[]|False
     * @throws Exception
     * @throws Exception
     */
    private function getDefaultHeaders(): array|false
    {
        if ($this->hasToken) {
            if ($this->isApiTokenExpired()) {
                if ($this->refreshToken()) { // relogin using stored credentials, because the token expired.
                    return ['Authorization' => "Bearer $this->apiToken"];
                } else {
                    // this can happen when refresh token fails, which may happen with an expired token and oauth login
                    return False;
                }
            } else {
                // Update the token use date and keep going
                $this->setApiTokenDate();
                return ['Authorization' => "Bearer $this->apiToken"];
            }
        } else {
            return False;
        }
    }

    /**
     * applies rawurlencode to bits of user-supplied data which will be passed directly to the data api as part of the request path
     *
     * @param string|int $data
     *
     * @return string
     */
    protected function prepareURLpart(string|int $data): string
    {
        return rawurlencode(trim($data));
    }

    /**
     * formats scripts for the data api
     *
     * @param array $scripts
     *
     * @return array
     */
    private function prepareScriptOptions(array $scripts): array
    {
        $preparedScript = [];
        foreach ($scripts as $script) {
            /**
             * The following works around a quirk of the 17 Data API. If you send a script parameter, but it's blank (unused), it chokes. So delete it.
             * prerequest and presort behavior is untested (with respect to this specific issue).
             **/
            switch ($script['type']) {
                case self::SCRIPT_PREREQUEST:
                case self::SCRIPT_PRESORT:
                    $scriptSuffix = $script['type'];
                    $preparedScript['script.' . $scriptSuffix] = $script['name'];
                    $preparedScript['script.' . $scriptSuffix . '.param'] = $script['param'];
                    break;
                case self::SCRIPT_POSTREQUEST:
                    $preparedScript['script'] = $script['name'];
                    if (array_key_exists('param', $script)) {
                        if (empty($script['param'])) {
                            unset($script['param']);
                        } else {
                            $preparedScript['script.param'] = $script['param'];
                        }
                    }
                    break;
                default:
                    break;
            }

        }

        return $preparedScript;
    }

    /**
     * @param array $portals
     *
     * @return array
     */
    private function preparePortalsOptions(array $portals): array
    {
        if (empty($portals)) {
            return [];
        }

        $portalList = [];

        foreach ($portals as $portal) {
            $portalList[] = $portal['name'];

            if (isset($portal['offset'])) {
                $options['offset.' . $portal['name']] = intval($portal['offset']);
            }

            if (isset($portal['limit'])) {
                $options['limit.' . $portal['name']] = intval($portal['limit']);
            }
        }

        $options['portal'] = $portalList;

        return $options;
    }

    /**
     * @param int $dateFormat
     *
     * @return array
     */
    private function prepareDateFormat(int $dateFormat): array
    {
        $payload = [];
        $payload['dateformats'] = match ($dateFormat) {
            self::DATE_FILELOCALE => 1,
            self::DATE_ISO8601 => 2,
            default => 0,
        };
        return $payload;
    }

    // CREDENTIAL MANAGEMENT

    /**
     * stores username and password for regular dapi logins, for token regeneration upon expiry
     *
     * @param string $user
     * @param string $pass
     *
     * @return True|False
     */
    protected function storeCredentials(string $user, string $pass): bool
    {
        if ($this->dapiUserName = $user) {
            if ($this->dapiUserPass = $pass) {
                return True;
            } else {
                return False;
            }
        } else {
            return False;
        }
    }

    /**
     * stores $oAuthRequestId and $oAuthIdentifier for oAuth dapi logins, for token regeneration upon expiry
     *
     * @param string $oAuthRequestId
     * @param string $oAuthIdentifier
     * @return True|False
     */
    protected function storeOAuth(string $oAuthRequestId, string $oAuthIdentifier): bool
    {
        if ($this->oAuthRequestId = $oAuthRequestId) {
            if ($this->oAuthIdentifier = $oAuthIdentifier) {
                return True;
            } else {
                return False;
            }
        } else {
            return False;
        }
    }

    // TOKEN MANAGEMENT

    /**
     *  Get API token returned after a successful login
     *
     * @return null|string
     */
    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    /**
     *  Set API token manually
     *
     * @param string $token
     * @param        $date
     *
     * @return True|False
     */
    public function setApiToken(string $token, $date = null): bool
    {
        if ($this->apiToken = $token) {
            if (!is_null($date)) {
                $this->apiTokenDate = $date;
            } else {
                $this->setApiTokenDate();
            }
            $this->hasToken = True;
            return True;
        } else {
            return False;
        }
    }

    /**
     * returns API token last use date, or False if there is no last use date.
     *
     * @return false|string|null
     */
    public function getApiTokenDate(): false|string|null
    {
        if (!is_null($this->apiTokenDate)) {
            return $this->apiTokenDate;
        } else {
            return False;
        }
    }

    /**
     * sets api token last used date. for internal library use. token lifetime is reset on each use in FM DAPI, hence this.
     *
     * @return True|False
     */
    public function setApiTokenDate(): bool
    {
        // calculate and then set token date
        // this function assumes it will be called in the context of using the token
        if ($this->apiTokenDate = time()) {
            return True;
        } else {
            return False;
        }
    }

    /**
     * answers the question: is the data api token in this class instance likely to be expired?
     *
     * @return True|False
     */
    public function isApiTokenExpired(): bool
    {
        // checks if token is OK to use
        if ($this->getApiTokenDate()) {
            $expiry = $this->apiTokenDate + (14 * 60); // actual tokens last 15 minutes. we'll be conservative here.
            if (time() > $expiry) {
                return True;
            } else {
                return False;
            }
        } else {
            return True; // if it's null, it has not been set, so it is "expired"
        }
    }

    /**
     * will refresh the token IF token was retreived via username/password login call sometime in the past
     *
     * @return True|False
     * @throws Exception
     * @throws Exception
     */
    public function refreshToken(): bool
    {
        // warning: cannot be called before login()
        if (!is_null($this->dapiUserName)) {
            if ($this->login($this->dapiUserName, $this->dapiUserPass)) {
                return True;
            } else {
                return False;
            }
        } elseif (!is_null($this->oAuthRequestId)) {
            if ($this->loginOauth($this->oAuthRequestId, $this->oAuthIdentifier)) {
                return True;
            } else {
                return False;
            }
        } else {
            return False;
        }
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function validateTokenWithServer(): bool
    {
        $response = $this->ClientRequest->request(
            'GET',
            "/$this->DAPIVersionString/validateSession",
            [
                'headers' => $this->getDefaultHeaders()
            ]
        );

        if ((int)$response->getBody()['messages'][0]['code'] === 0) {
            return True;
        } else {
            return False;
        }
    }

    // METADATA OPERATIONS

    /**
     * @return array
     * @throws Exception
     */
    public function getProductInfo(): array
    {
        // Send curl request
        $response = $this->ClientRequest->request(
            'GET',
            "/$this->DAPIVersionString/productInfo",
            [
                'headers' => $this->getDefaultHeaders(),
                'json' => []
            ]
        );
        return $response->getRawResponse();
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getDatabaseNames(): array
    {
        // Send curl request
        $response = $this->ClientRequest->request(
            'GET',
            "/$this->DAPIVersionString/databases",
            [
                'headers' => $this->getDefaultHeaders(),
                'json' => []
            ]
        );
        return $response->getRawResponse();
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getLayoutNames(): array
    {
        // Send curl request
        $response = $this->ClientRequest->request(
            'GET',
            "/$this->DAPIVersionString/databases/$this->apiDatabase/layouts",
            [
                'headers' => $this->getDefaultHeaders(),
                'json' => []
            ]
        );
        return $response->getRawResponse();
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getScriptNames(): array
    {
        // Send curl request
        $response = $this->ClientRequest->request(
            'GET',
            "/$this->DAPIVersionString/databases/$this->apiDatabase/scripts",
            [
                'headers' => $this->getDefaultHeaders(),
                'json' => []
            ]
        );
        return $response->getRawResponse();
    }

    /**
     * @param string $layout
     * @param        $recordId
     *
     * @return array
     * @throws Exception
     */
    public function getLayoutMetadata(string $layout, $recordId = null): array
    {
        // Prepare options
        $recordId = $this->prepareURLpart($recordId);
        $jsonOptions = [];
        $metadataFormat = '/metadata';
        if (!empty($recordId)) {
            $jsonOptions['recordId'] = $recordId;
            $metadataFormat = '';
        }
        // Send curl request
        $response = $this->ClientRequest->request(
            'GET',
            "/$this->DAPIVersionString/databases/$this->apiDatabase/layouts/$layout" . $metadataFormat,
            [
                'headers' => $this->getDefaultHeaders(),
                'json' => array_merge(
                    $jsonOptions
                ),
            ]
        );
        return $response->getRawResponse();
    }

    /**
     * set DAPIVersion separate from constructor
     *
     * @param DapiVersion $version
     * @return void
     */
    public function setDAPIVersion(DapiVersion $version): void
    {
        $this->DAPIVersion = $version;
    }

    /**
     * handles a couple common errors
     *
     * @param $e
     * @return array|bool
     */
    private function dAPIerrorHandler($e): array|bool
    {
        // this logic was previously in a single function in the library, but this is a useful feature.
        // will return True (or be truthy) if the error was or can be handled silently.
        $code = $e->getCode();
        return match ($code) {
            401 => [],
            952 => True,
            default => False,
        };
    }
}
class_alias(HttpClientType::class, 'RCConsulting\\FileMakerApi\\HttpClientType');
