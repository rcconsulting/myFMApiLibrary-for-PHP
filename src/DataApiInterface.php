<?php

namespace Lesterius\FileMakerApi;

/**
 * Interface DataApiInterface
 * @package Lesterius\FileMakerApi
 */
interface DataApiInterface
{
    /**
     * @param string $apiUsername
     * @param string $apiPassword
     *
     * @return mixed
     */
    public function login(string $apiUsername, string $apiPassword);

    /**
     * @param string $oAuthRequestId
     * @param string $oAuthIdentifier
     *
     * @return mixed
     */
    public function loginOauth(string $oAuthRequestId, string $oAuthIdentifier);

    /**
     *  Close the connection with FileMaker Server API
     * @return mixed
     * @throws Exception
     */
    public function logout();

    /**
     * @param       $layout
     * @param array $data
     * @param array $scripts
     * @param array $portalData
     *
     * @return mixed
     */
    public function createRecord($layout, array $data, array $scripts = [], array $portalData = []);

    /**
     * @param       $layout
     * @param       $recordId
     * @param array $data
     * @param null  $lastModificationId
     * @param array $portalData
     * @param array $scripts
     *
     * @return mixed
     * @throws Exception
     */
    public function editRecord($layout, $recordId, array $data, $lastModificationId = null, array $portalData = [], array $scripts = []);

    /**
     * Duplicate an existing record
     *
     * @param       $layout
     * @param       $recordId
     * @param array $scripts
     * @return mixed
     * @throws Exception
     */
      public function duplicateRecord($layout, $recordId, array $scripts = []);

      /**
       * Delete record by id
       *
       * @param       $layout
       * @param       $recordId
       * @param array $scripts
       *
       * @throws Exception
       */
      public function deleteRecord($layout, $recordId, array $scripts = []);

    /**
     * Get record detail
     *
     * @param       $layout
     * @param       $recordId
     * @param array $portalOptions
     * @param array $scripts
     * @param null  $responseLayout
     *
     * @return mixed
     * @throws Exception
     */
    public function getRecord($layout, $recordId, array $portalOptions = [], array $scripts = [], $responseLayout = null);

    /**
     * @param       $layout
     * @param null  $sort
     * @param null  $offset
     * @param null  $limit
     * @param array $portals
     * @param array $scripts
     * @param null  $responseLayout
     *
     * @return mixed
     */
    public function getRecords($layout, $sort = null, $offset = null, $limit = null, array $portals = [], array $scripts = [], $responseLayout = null);

    /**
     * @param $layout
     * @param $recordId
     * @param $containerFieldName
     * @param $containerFieldRepetition
     * @param $filepath
     * @param $filename
     *
     * @return mixed
     */
    public function uploadToContainer($layout, $recordId, $containerFieldName, $containerFieldRepetition, $filepath, $filename);

    /**
     * @param        $layout
     * @param        $query
     * @param null   $sort
     * @param null   $offset
     * @param null   $limit
     * @param array  $portals
     * @param array  $scripts
     * @param string $responseLayout
     *
     * @return mixed
     */
    public function findRecords($layout, array $query, $sort = null, $offset = null, $limit = null, array $portals = [], array $scripts = [], $responseLayout = null);

    /**
     * Execute script alone
     *
     * @param     $layout
     * @param     $scriptName
     * @param null $scriptParam
     * @return mixed
     * @throws Exception
     */
    public function executeScript($layout, $scriptName, $scriptParam = null);

    /**
     * @param       $layout
     * @param array $globalFields
     *
     * @return mixed
     */
    public function setGlobalFields($layout, array $globalFields);

    /**
     * @return mixed
     */
    public function getApiToken();

    /**
     * @param string $token
     *
     * @return True|False
     */
    public function setApiToken(string $token, $date = null);

    /**
     * returns API token last use date, or False if there is no last use date.
     *
     * @return string|False
     */
    public function getApiTokenDate();

    /**
    * sets api token last used date. for internal library use. token lifetime is reset on each use in FM DAPI, hence this.
    *
    * @return True|False
    */
    public function setApiTokenDate();

    /**
     * @return True|False
     */
    public function isApiTokenExpired();

    /**
     * @return True/False
     */
    public function refreshToken();

    /**
     * @return mixed
     * @throws Exception
     */
    public function getProductInfo();

    /**
     * @return mixed
     * @throws Exception
     */
    public function getDatabaseNames();

    /**
     * @return mixed
     * @throws Exception
     */
    public function getLayoutNames();

    /**
     * @return mixed
     * @throws Exception
     */
    public function getScriptNames();

    /**
     * @param $layout
     * @param null $recordId
     *
     * @throws Exception
     * @return mixed
     */
    public function getLayoutMetadata($layout, $recordId = null);
}
