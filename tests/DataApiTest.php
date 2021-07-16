<?php

namespace RCConsulting\FileMakerApi;

use PHPUnit\Framework\TestCase;
use \RCConsulting\FileMakerApi\DataApi;

require_once __DIR__.'/../vendor/autoload.php';

/**
 * Class DataApiTest
 * @package RCConsulting\DataApi
 */
final class DataApiTest extends TestCase
{
    protected $dataApi;

    /**
     * DataApiTest constructor.
     *
     * @param      $url
     * @param      $database
     * @param bool $sslVerify
     *
     * @throws \Exception
     */
    public function __construct($url, $database, $sslVerify = true)
    {
        $this->dataApi = new DataApi($url, $database, $sslVerify);
    }

    /**
     * @test
     * @param $username
     * @param $password
     *
     * @return null|string
     */
    public function testLogin($username, $password)
    {
        try {
            $this->dataApi->login($username, $password);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return $this->dataApi->getApiToken();
    }

    /**
     * @test
     * @param $oAuthRequestId
     * @param $oAuthIdentifier
     *
     * @return null|string
     */
    public function testLoginOauth($oAuthRequestId, $oAuthIdentifier)
    {
        try {
            $this->dataApi->loginOauth($oAuthRequestId, $oAuthIdentifier);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return $this->dataApi->getApiToken();
    }

    /**
     * @test
     * @param $layout
     * @param $data
     *
     * @return string
     */
    public function testCreateRecord($layout, array $data)
    {
        $record_id = null;

        try {
            $record_id = $this->dataApi->createRecord($layout, $data);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return $record_id;
    }

    /**
     * @test
     * @param       $layout
     * @param       $recordId
     * @param array $data
     * @param null  $lastModificationId
     *
     * @return mixed|string
     */
    public function testEditRecord($layout, $recordId, array $data, $lastModificationId = null)
    {
        try {
            $record_id = $this->dataApi->editRecord($layout, $recordId, $data, $lastModificationId);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return $record_id;
    }

    /**
     * @test
     * @param      $layout
     * @param      $recordId
     * @param null $offset
     * @param null $range
     * @param null $portal
     *
     * @return mixed|string
     */
    public function testGetRecord($layout, $recordId, $offset = null, $range = null, $portal = null)
    {
        try {
            $record_id = $this->dataApi->getRecord($layout, $recordId, $offset, $range, $portal);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return $record_id;
    }

    /**
     * @test
     * @param      $layout
     * @param null $sort
     * @param null $offset
     * @param null $range
     * @param null $portal
     *
     * @return mixed|string
     */
    public function testgetRecords($layout, $sort = null, $offset = null, $range = null, $portal = null)
    {
        try {
            $result = $this->dataApi->getRecords($layout, $sort, $offset, $range, $portal);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return $result;
    }

    /**
     * @test
     * @param      $layout
     * @param      $query
     *
     * @param null $sort
     * @param null $offset
     * @param null $range
     * @param null $portal
     *
     * @return string
     */
    public function testFindRecords($layout, $query, $sort = null, $offset = null, $range = null, $portal = null)
    {
        try {
            $result = $this->dataApi->findRecords($layout, $query, $sort, $offset, $range, $portal);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return $result;
    }

    /**
     * @test
     * @param       $layout
     * @param array $globalFields
     *
     * @return mixed|string
     */
    public function testSetGlobalFields($layout, array $globalFields)
    {
        try {
            $result = $this->dataApi->setGlobalFields($layout, $globalFields);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return $result;
    }

    /**
     * @test
     * @param $layout
     * @param $record_id
     *
     * @return string
     */
    public function testDeleteRecord($layout, $record_id)
    {
        try {
            $this->dataApi->deleteRecord($layout, $record_id);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return $record_id;
    }

    /**
     * @test
     * @return string
     */
    public function testLogout()
    {
        try {
            $this->dataApi->logout();
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return true;
    }
}
