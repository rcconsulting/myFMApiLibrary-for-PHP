FileMaker 17 Data API wrapper - myFMApiLibrary for PHP
=======================

## Team
[Lesterius](https://www.lesterius.com "Lesterius") is first and foremost a collective of FileMaker and Web developers who are experts and passionated.\
Sharing knowledge takes part of our DNA, that's why we developed this library to make the FileMaker Data API easy-to-use with PHP.\
Break the limits of your application!\
![Lesterius logo](http://i1.createsend1.com/ei/r/29/D33/DFF/183501/csfinal/Mailing_Lesterius-logo.png "Lesterius")

[Richard Carlton Consulting](https://rcconsulting.com/ "Richard Carlton Consulting") is a full-stack FileMaker development consultancy, serving clients globally with any and all FileMaker-related needs. We also publish the largest and most [complete video training](https://fmtraining.tv/) course for the FileMaker Community.

## Description
This library is a PHP wrapper of the FileMaker Data API.

You will be able to use every functions like it's documented in your FileMaker server Data Api documentation (accessible via https://[your server domain]/fmi/data/apidoc).

General FileMaker document on the Data API is available [here](https://fmhelp.filemaker.com/docs/17/en/dataapi/)

## Rationale
This fork is to bring greater out of the box compatibility to the myFMApiLibrary. The current mainline code from Lesterius does not function on MacOS FileMaker Server installations, nor Windows FileMaker Server installations without modifying PHP.ini to enable the mbstring extension. The Lesterius library functions extremely narrowly: If you're on FMS 17 on Windows, and you only do simple tasks like creating a record (no scripts being called as part of record creation), it will work.

We submitted these changes to Lesterius, who have declined to accept the improvements. So, we forked it.

A short list of improvements we've made:
1. remove curl_escape from CurlClient, and wrap the data API path components in rawurlencode. curl_escape on OSX systems urlencodes the slashes of the path, which results in 404 errors when using the Data API on OSX.
2. improve script handling so when you use a pre-execution or post-execution script, without a parameter, it does not send a blank parameter confusing the Data API (it causes dapi to reject the request).
3. A filename parameter was added to the container data upload function, so when passing container data to DAPI from a webform you can tell FileMaker the file's name (it otherwise defaults to the php temp filename, which is not useful).
4. Documentation improvements.
5. TODO: implement polyfill for mbstring extension.  Until then, please ensure the mbstring extension is enabled in PHP.ini

## Requirements

- PHP >= 5.5
- PHP cURL extension
- PHP mbstring extension

## Installation

The recommended way to install it is through [Composer](http://getcomposer.org).

```bash
composer require rcconsulting/myfmapilibrary-for-php
```

After installing, you need to notate that you'll use this library, and require Composer's autoloader:

```php
use \Lesterius\FileMakerApi\DataApi;
require_once __DIR__ . '/vendor/autoload.php';
```

# Usage

## Prepare your FileMaker solution

1. Enable the FileMaker Data API option on your FileMaker server admin console.
2. Create a specific user in your FileMaker database with the 'fmrest' privilege
3. Define records & layouts access for this user

## Use the library

### Login

Login with credentials:
```php
$dataApi = new \Lesterius\FileMakerApi\DataApi('https://test.fmconnection.com/fmi/data', 'MyDatabase');
$dataApi->login('filemaker api user', 'filemaker api password');
```

Login with oauth:
```php
$dataApi = new \Lesterius\FileMakerApi\DataApi('https://test.fmconnection.com/fmi/data', 'MyDatabase');
$dataApi->loginOauth('oAuthRequestId', 'oAuthIdentifier');
```

### Logout

```php
// Call login method first

$dataApi->logout();
```

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
        'type'  => Lesterius\FileMakerApi\DataApi::SCRIPT_PREREQUEST
    ],
    [
        'name'  => 'SendEmail',
        'param' => 'johndoe@acme.inc',
        'type'  => Lesterius\FileMakerApi\DataApi::SCRIPT_POSTREQUEST
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
  $recordId = $dataApi->editRecord('layout name', $recordId, $data, $lastModificationId, $$portalData, $scripts);
} catch(\Exception $e) {
  // handle exception
}
```

### Get record

```php
// Call login method first

$portals = [
    [
        'portal' => 'Portal1',
        'limit'  => 10
    ],
    [ 
        'portal' => 'Portal2',
        'offset' => 3
    ]
];

try {
  $record = $dataApi->getRecord('layout name', $recordId, $portals, $scripts);
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
  $record = $dataApi->getRecords('layout name', $sort, $offset, $limit, $portals, $scripts);
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
  $results = $dataApi->findRecords('layout name', $query, $sort, $offset, $limit, $portals, $scripts, $responseLayout);
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
$dataApi->setApiToken($token);

// returns current api token (without checking if it's valid)
$token = $dataApi->getApiToken();

// to check if the token is expired:
if ($dataApi->isApiTokenExpired()) {
// do stuff
}

// to refresh the token in the case you've ever previously logged into the data api with this instance of the class
// currently only works with a data api username/password combo (ie not oauth)
if ($dataApi->refreshToken()) {
// success
}

