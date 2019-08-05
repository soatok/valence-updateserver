# Valence - Update Server

[![Support on Patreon](https://img.shields.io/endpoint.svg?url=https%3A%2F%2Fshieldsio-patreon.herokuapp.com%2Fsoatok&style=flat)](https://patreon.com/soatok)

This is a standalone update server for the Valence project.

## System Requirements

* PHP 7.3+ with ext-sodium
* PostgreSQL 10 or newer

## Install / Setup Instructions

### Getting the Code

For best results, clone the Git repository.

```
git clone https://github.com/soatok/valence-updateserver
cd valence-updateserver
```

### Setting Up the Database

Create a `local/settings.php` file with your database
credentials. A sample file looks like this:

```php
<?php
use Soatok\DholeCrypto\Keyring;

$keyring = new Keyring();
return [
    'database' => [
        'dsn' => 'pgsql:host=localhost;dbname=valence',
        'username' => 'username',
        'password' => 'password',
        'options' => []
    ],

    'quill' => [
        'url' => 'http://localhost:8080',
        'client-id' => 'get from chronicle',
        'server-public-key' => 'get from chronicle',
        'client-secret-key' => 'generate later'
    ]
];
```

Once your database is configured, run `bin/setup` to create the
necessary database tables.

### Chronicle Setup

You'll want to setup **at least one** Chronicle instance. You can
get [the source code and install instructions from Chronicle](https://github.com/paragonie/chronicle) 
on Github.

To create a Chronicle keypair, run `bin/create-chronicle-key`.

## Managing Your Valence Server

### Creating Publisher Accounts

To create a new Publisher, you need to run the `bin/create-publisher`
command, like so:

```
bin/create-publisher publisher-name-goes-here
```

Publisher names should be alphanumeric with dashes and underscores.

### Creating Projects

Run `bin/create-project` with the publisher name followed by the
project name, like so:

```
bin/create-publisher publisher-name new-project-name
```

Project names should be alphanumeric with dashes and underscores.

### Creating Publisher Tokens

Run `bin/grant-publisher-token` to generate an access token for
publishers to run [with the developer tools](https://github.com/soatok/valence-devtools).

```
bin/grant-publisher-token publisher-name
```

### Creating Access Tokens

Run `bin/grant-access`. This is an interactive CLI script that will
prompt you for specific information.

## Using the Update Server

Once you've setup your Valence Update Server and gotten familiar 
with its inner workings, you'll want to look at the
[developer tools](https://github.com/soatok/valence-devtools).

You'll be mostly using that API to create, sign, and publish
updates to your software projects.
