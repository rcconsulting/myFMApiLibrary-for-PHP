FileMaker 17/18/19/21/22 Data API wrapper - myFMApiLibrary for PHP
=======================

## Team
[Lesterius](https://www.lesterius.com "Lesterius") is a European FileMaker Business Alliance Platinum member that operates in Belgium, France, the Netherlands, Portugal and Spain. We are creative business consultants who co-create FileMaker Platform based solutions with our customers.\
Sharing knowledge takes part of our DNA, that's why we developed this library to make the FileMaker Data API easy-to-use with PHP.\
Break the limits of your application!\
![Lesterius logo](http://i1.createsend1.com/ei/r/29/D33/DFF/183501/csfinal/Mailing_Lesterius-logo.png "Lesterius")

[Richard Carlton Consulting](https://rcconsulting.com/ "Richard Carlton Consulting") is a full-stack Claris FileMaker development consultancy, serving clients globally with any and all FileMaker-related needs. We also publish the largest and most [complete video training](https://fmtraining.tv/) course for the FileMaker Community.

## Description
This library is a PHP wrapper for the Claris FileMaker Data API. It supports version 17/18/19 Data API commands, though if you use an 18 command (metadata commands for ex) on 17, it will fail.

You will be able to use every functions like it's documented in your FileMaker server Data Api documentation (accessible via https://[your server domain]/fmi/data/apidoc).

General FileMaker document on the FMS 17 Data API is available [here](https://fmhelp.filemaker.com/docs/17/en/dataapi/)

General FileMaker document on the FMS 18 Data API is available [here](https://fmhelp.filemaker.com/docs/18/en/dataapi/)

General FileMaker document on the FMS 19+ Data API is available [here](https://help.claris.com/en/data-api-guide/)

## Rationale
This fork is to bring greater out of the box compatibility to the myFMApiLibrary, vs the Lesterius upstream code. We're immensely grateful for the hard work Lesterius put in.

## Requirements

### Library version <2.1.0:

- PHP >= 5.5
- PHP cURL extension
- PHP mbstring extension

### Library version >=2.1.0:

- PHP >= 7.1
- PHP cURL extension
- PHP mbstring extension

### Library version >=3.0.0:

- PHP >= 8.3
- PHP cURL extension
- PHP mbstring extension
- Guzzle

## Installation

The recommended way to install it is through [Composer](http://getcomposer.org).

```bash
composer require rcconsulting/myfmapilibrary-for-php:\>=3.0.0
```

After installing, you need to notate that you'll use this library, and require Composer's autoloader:

```php
use \RCConsulting\FileMakerApi\DataApi;
require_once __DIR__ . '/vendor/autoload.php';
```

# Usage

## Prepare your FileMaker solution

1. Enable the FileMaker Data API on your FileMaker Server Admin Console.
2. Create a user in your FileMaker database, with a custom privilege set that has at least the 'fmrest' extended privilege
3. Define records & layouts access for this user

## Use the library

### Login

Login with credentials:
```php
// Please note that the library does not currently support external data source authentication
$dataApi = new \RCConsulting\FileMakerApi\DataApi('https://test.fmconnection.com/fmi/data', 'MyDatabase');
$dataApi->login('filemaker api user', 'filemaker api password');
```

One-line example of Login with Credentials:
```php
// Arguments: DAPI URL, Database Name, DAPI User, DAPI Password, SSL Verification Enabled?, Force Legacy HTTP 1.1?
$dataApi = new \RCConsulting\FileMakerApi\DataApi('https://test.fmconnection.com/fmi/data','MyDatabase', "DAP_User", "DAPI_Pass_1234~", True, False);
```


Login with oauth:
```php
// Please note that RCC has not tested OAuth logins. We welcome any PR's which improve support.
$dataApi = new \RCConsulting\FileMakerApi\DataApi('https://test.fmconnection.com/fmi/data', 'MyDatabase');
$dataApi->loginOauth('oAuthRequestId', 'oAuthIdentifier');
```

### Logout

```php
// It is highly recommended to log out at each script or large process end. The Data API tokens last 15 minutes after last use, and so will hold a slot open for 15 minutes after your last connection if you do not clean up after yourself. Additionally, logout() does not destroy the DataAPI object you created (via new) previously, but it does clean out any authorization tokens from the object.

$dataApi->logout();
```

### HTTP Client Options

The library supports multiple HTTP client implementations through an abstraction layer. You can choose between cURL-based and Guzzle-based HTTP clients using either type-safe enumerations or backward-compatible strings.

#### Available HTTP Clients

**CurlClient (Default)**
- Uses PHP's native cURL extension
- Lightweight and fast
- Default choice for backward compatibility

**GuzzleClient**
- Uses the Guzzle HTTP client library
- Better HTTP/2 support
- Advanced features like middleware support
- Better error handling and debugging capabilities

#### Usage Examples

**Using HttpClientType Enumeration (Recommended):**
```php
use RCConsulting\FileMakerApi\DataApi;

// Using DataApi\HttpClientType::CURL (default)
$dataApi = new DataApi(
    'https://test.fmconnection.com/fmi/data',
    'MyDatabase',
    'username',
    'password',
    true,                       // SSL verification
    false,                      // Force legacy HTTP
    false,                      // Return response object
    HttpClient::CURL,           // HTTP client type (enum)
    DapiVersion::V1             // Data API version (enum)
);

// Using DataApi\HttpClientType::GUZZLE
$dataApi = new DataApi(
    'https://test.fmconnection.com/fmi/data',
    'MyDatabase',
    'username',
    'password',
    true,                       // SSL verification
    false,                      // Force legacy HTTP
    false,                      // Return response object
    HttpClient::GUZZLE,         // HTTP client type (enum)
    DapiVersion::VLATEST        // Data API version (enum)
);

// Default behavior (uses HttpClientType::CURL)
$dataApi = new DataApi(
    'https://test.fmconnection.com/fmi/data',
    'MyDatabase',
    'username',
    'password'
);
```

**Using String Values (Backward Compatibility):**
```php
// Using string 'curl'
$dataApi = new DataApi(
    'https://test.fmconnection.com/fmi/data',
    'MyDatabase',
    'username',
    'password',
    true,               // SSL verification
    false,              // Force legacy HTTP
    false,              // Return response object
    'curl',             // HTTP client type (string)
    DapiVersion::V1     // Data API version (enum)
);

// Using string 'guzzle'
$dataApi = new DataApi(
    'https://test.fmconnection.com/fmi/data',
    'MyDatabase',
    'username',
    'password',
    true,               // SSL verification
    false,              // Force legacy HTTP
    false,              // Return response object
    'guzzle',           // HTTP client type (string)
    DapiVersion::V1     // Data API version (enum)
);
```

**Constructor Parameters:**
```php
new DataApi(
    string $apiUrl,                                     // FileMaker Data API URL
    string $apiDatabase,                                // Database name
    string $apiUser = null,                             // Username (optional)
    string $apiPassword = null,                         // Password (optional)
    bool $sslVerify = true,                             // Verify SSL certificates
    bool $forceLegacyHTTP = false,                      // Force HTTP/1.1
    bool $returnResponseObject = false,                 // Return full response objects
    HttpClient|string $httpClient = HttpClient::CURL    // HTTP client (enum or string)
    DapiVersion $dapiVersion = DapiVersion::V1          //specify Data API version (enum) currently v1, v2 or vLatest
);
```

#### Benefits of HTTP Client Abstraction

- **Flexibility**: Switch between HTTP implementations without changing your code
- **Future-proofing**: Easy migration to new HTTP client libraries
- **Testing**: Better mocking capabilities with Guzzle
- **Performance**: Choose the best client for your specific needs
- **Backward Compatibility**: Existing code continues to work unchanged

### Create record

```php
// Call login method first

$data = [
    'FirstName'         => 'John',
    'LastName'          => 'Doe',
    'email'             => 'johndoe@acme.inc',
    'RepeatingField(1)' => 'Test'
];

$scripts = [
    [
        'name'  => 'ValidateUser',
        'param' => 'johndoe@acme.inc',
        'type'  => RCConsulting\FileMakerApi\DataApi::SCRIPT_PREREQUEST
    ],
    [
        'name'  => 'SendEmail',
        'param' => 'johndoe@acme.inc',
        'type'  => RCConsulting\FileMakerApi\DataApi::SCRIPT_POSTREQUEST
    ]
];

$portalData = [
    'lunchDate' => '17/04/2013',
    'lunchPlace' => 'Acme Inc.'
];

try {
    $recordId = $dataApi->createRecord('layout name', $data, $scripts, $portalData);
} catch(\Exception $e) {
  // handle exception
}
```

### Delete record

```php
// Call login method first

try {
  $dataApi->deleteRecord('layout name', $recordId, $script);
} catch(\Exception $e) {
  // handle exception
}
```

### Edit record

```php
// Call login method first

try {
  $recordId = $dataApi->editRecord('layout name', $recordId, $data, $lastModificationId, $portalData, $scripts);
} catch(\Exception $e) {
  // handle exception
}
```

### Get record

```php
// Call login method first

$portals = [
    [
        'name'   => 'Portal1',
        'limit'  => 10
    ],
    [ 
        'name'   => 'Portal2',
        'offset' => 3
    ]
];

try {
  $record = $dataApi->getRecord('layout name', $recordId, $portals, $scripts, $responseLayout, $dateFormat);
} catch(\Exception $e) {
  // handle exception
}
```

### Get records

```php
// Call login method first

$sort = [
    [
        'fieldName' => 'FirstName',
        'sortOrder' => 'ascend'
    ],
    [
        'fieldName' => 'City',
        'sortOrder' => 'descend'
    ]
];

try {
  $record = $dataApi->getRecords('layout name', $sort, $offset, $limit, $portals, $scripts, $responseLayout, $dateFormat);
} catch(\Exception $e) {
  // handle exception
}
```

### Find records

```php
// Call login method first
$query = [
    [
        'fields'  => [
            ['fieldname' => 'FirstName', 'fieldvalue' => '==Test'],
            ['fieldname' => 'LastName', 'fieldvalue' => '==Test'],
        ],
        'options' => [
            'omit' => false
        ]
    ]
];

try {
  $results = $dataApi->findRecords('layout name', $query, $sort, $offset, $limit, $portals, $scripts, $responseLayout, $dateFormat);
} catch(\Exception $e) {
  // handle exception
}
```

### Blind Fire Script
```php
try {
    $dataApi->executeScript($layout, $scriptName, $scriptParameters);
} catch(\Exception $e) {
    // handle exception
}
```

### Set global fields

```php
// Call login method first

$data = [
  'FieldName1'	=> 'value',
  'FieldName2'	=> 'value'
];

try {
  $dataApi->setGlobalFields('layout name', $data);
} catch(\Exception $e) {
  // handle exception
}
```

### Upload file to container
#### File Passed Via WebForm
```php
// Call login method first

$containerFieldName       = 'Picture';
$containerFieldRepetition = 1;
// replace 'upload' below with the name="value" of the file input element of your web form
$filepath                 = $_FILES['upload']['tmp_name'];
$filename                 = $_FILES['upload']['name'];

try {
  $dataApi->uploadToContainer('layout name', $recordId, $containerFieldName, $containerFieldRepetition, $filepath, $filename);
} catch(\Exception $e) {
  // handle exception
}
```

#### File On Server
```php
// Call login method first

$containerFieldName       = 'Picture';
$containerFieldRepetition = 1;
$filepath                 = '/usr/home/acme/pictures/photo.jpg';

try {
  $dataApi->uploadToContainer('layout name', $recordId, $containerFieldName, $containerFieldRepetition, $filepath);
} catch(\Exception $e) {
  // handle exception
}
```
## library helper methods

#### token usage
```php
// useful when not explicitly logging into data api, but already have valid token
// note that this implicitly sets the token retrieval date to now()
$dataApi->setApiToken($token);
// also supported
$dataApi->setApiToken($token, $tokenDate); // + unix timestamp

// returns current api token (without checking if it's valid)
$token = $dataApi->getApiToken();

// to check if the token is expired:
if ($dataApi->isApiTokenExpired()) {
// token expired
} else {
// do stuff
}

// to refresh the token in the case you've ever previously logged into the data api with this instance of the class
// currently only works with a data api username/password combo (ie not oauth)
if ($dataApi->refreshToken()) {
// success
}

// Validate whether the session authorization token we're using ... is actually valid ... according to FileMaker Server
if ($dataApi->validateTokenWithServer()){
// token is valid according to FMS!
} else {
// token has expired according to FMS!
}

```

#### update object settings
```php
//use this if you need to explicitly use v1 or v2. The default is v1, any future versions will be added. vLatest is also supported.
$dataApi->setDAPIVersion($dapiVersion)
```

## ToDo:

- ~~replace all curl with [guzzle](https://github.com/guzzle/guzzle "GuzzleHTTP")~~, while adding [certainty](https://github.com/paragonie/certainty "Certainty") and [monolog](https://github.com/Seldaek/monolog "monolog")
