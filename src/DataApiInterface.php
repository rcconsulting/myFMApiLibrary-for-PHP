<?php

namespace RCConsulting\FileMakerApi;
use RCConsulting\FileMakerApi\Exception\Exception;

/**
 * Interface DataApiInterface
 * @package RCConsulting\FileMakerApi
 */
interface DataApiInterface
{
    /**
     * @param string $apiUsername
     * @param string $apiPassword
     *
     * @return $this
     */
    public function login(string $apiUsername, string $apiPassword): DataApiInterface;

    /**
     * @param string $oAuthRequestId
     * @param string $oAuthIdentifier
     *
     * @return mixed
     */
    public function loginOauth(string $oAuthRequestId, string $oAuthIdentifier): DataApiInterface;

    /**
     *  Close the connection with FileMaker Server API
     * @return mixed
     * @throws Exception
     */
    public function logout(): DataApiInterface;

    /**
     * @param string $layout
     * @param array $data
     * @param array $scripts
     * @param array $portalData
     *
     * @return mixed
     */
    public function createRecord(string $layout, array $data, array $scripts = [], array $portalData = []): mixed;

    /**
     * @param string $layout
     * @param       $recordId
     * @param array $data
     * @param null  $lastModificationId
     * @param array $portalData
     * @param array $scripts
     *
     * @return mixed
     * @throws Exception
     */
    public function editRecord(string $layout, $recordId, array $data, $lastModificationId = null, array $portalData = [], array $scripts = []): mixed;

    /**
     * Duplicate an existing record
     *
     * @param string $layout
     * @param       $recordId
     * @param array $scripts
     * @return mixed
     * @throws Exception
     */
      public function duplicateRecord(string $layout, $recordId, array $scripts = []): mixed;

      /**
       * Delete record by id
       *
       * @param string $layout
       * @param       $recordId
       * @param array $scripts
       *
       * @throws Exception
       */
      public function deleteRecord(string $layout, $recordId, array $scripts = []): void;

    /**
     * Get record detail
     *
     * @param string $layout
     * @param       $recordId
     * @param array $portalOptions
     * @param array $scripts
     * @param null $responseLayout
     * @param int|null $dateFormat
     * @return mixed
     * @throws Exception
     */
    public function getRecord(string $layout, $recordId, array $portalOptions = [], array $scripts = [], $responseLayout = null, ?int $dateFormat = null): mixed;

    /**
     * @param string $layout
     * @param ?string   $sort
     * @param ?int   $offset
     * @param ?int   $limit
     * @param array  $portals
     * @param array  $scripts
     * @param ?string $responseLayout
     * @param ?int $dateFormat
     * @return array|Response
     */
    public function getRecords(string $layout, ?string $sort = null, ?int $offset = null, ?int $limit = null, array $portals = [], array $scripts = [], string $responseLayout = null, ?int $dateFormat = null): array|Response;

    /**
     * @param string $layout
     * @param        $recordId
     * @param string $containerFieldName
     * @param        $containerFieldRepetition
     * @param string $filepath
     * @param string|null $filename
     *
     * @return mixed
     */
    public function uploadToContainer(string $layout, $recordId, string $containerFieldName, $containerFieldRepetition, string $filepath, ?string $filename = null): bool;

    /**
     * @param string $layout
     * @param array $query
     * @param null $sort
     * @param null $offset
     * @param null $limit
     * @param array $portals
     * @param array $scripts
     * @param string|null $responseLayout
     *
     * @return array|bool|Response
     */
    public function findRecords(string $layout, array $query, $sort = null, $offset = null, $limit = null, array $portals = [], array $scripts = [], string $responseLayout = null): array|bool|Response;

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
    public function executeScript(string $layout, string $scriptName, string $scriptParam = null): string|true|Response;

    /**
     * @param string $layout
     * @param array $globalFields
     *
     * @return array|Response
     */
    public function setGlobalFields(string $layout, array $globalFields): array|Response;

    /**
     * @return string|null
     */
    public function getApiToken(): ?string;

    /**
     * @param string $token
     * @param        $date
     *
     * @return True|False
     */
    public function setApiToken(string $token, $date = null): bool;

    /**
     * returns the API token last use date, or False if there is no last use date.
     *
     * @return false|string|null
     */
    public function getApiTokenDate(): false|string|null;

    /**
    * sets api token last used date. for internal library use. token lifetime is reset on each use in FM DAPI, hence this.
    *
    * @return True|False
    */
    public function setApiTokenDate(): bool;

    /**
     * @return True|False
     */
    public function isApiTokenExpired(): bool;

    /**
     * @return True/False
     */
    public function refreshToken(): bool;

    /**
     * @return mixed
     * @throws Exception
     */
    public function getProductInfo(): array;

    /**
     * @return mixed
     * @throws Exception
     */
    public function getDatabaseNames(): array;

    /**
     * @return mixed
     * @throws Exception
     */
    public function getLayoutNames(): array;

    /**
     * @return mixed
     * @throws Exception
     */
    public function getScriptNames(): array;

    /**
     * @param string $layout
     * @param null   $recordId
     *
     * @throws Exception
     * @return mixed
     */
    public function getLayoutMetadata(string $layout, $recordId = null): array;

    /**
     * set DAPIVersion separate from constructor
     *
     * @param DapiVersion $version
     * @return void
     */
    public function setDAPIVersion(DapiVersion $version): void;
}
