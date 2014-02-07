HVRawConnectorPHP
=================

Simple low level PHP library to connect to
[Microsoft HealthVault](https://www.healthvault.com/).


Installation
------------

HVRawConnectorPHP depends on some PHP libraries. But you can simply use composer to install HVRawConnectorPHP and it's dependencies.

To add HVRawConnectorPHP as a library in your project, add something like that to
the 'require' section of your `composer.json`:

```json
{
  "require": {
    "biologis/hv-raw-connector": "dev-master"
  }
}
```

If composer complains about an unknown pear channel, add this to your `composer.json`:
```json
{
  "repositories": [
    {
      "type": "pear",
      "url": "http://pear.php.net"
    }
  ]
}
```

Usage
-----

Due to the fact that HVRawConnectorPHP is a low level libary, we don't want to provide brief instructions here. But if you're interested you can have a look at the demo_app source code that comes with the lib.

To develop HealthVault applications using PHP we recommend to use the [HVClientLib](http://mkalkbrenner.github.io/HVClientLibPHP/) instead which has been created on top of HVRawConnectorPHP.


Demo
----

The demo_app included in this repository queries a user's HealthVault record
for all "[Things](http://developer.healthvault.com/pages/types/types.aspx)" and
dumps the content. By default it uses the US pre production instance of
HealthVault.

Simply put the HVRawConnectorPHP folder on a web server and access
"demo_app/index.php".


Licence
-------

[GPLv2](https://raw.github.com/mkalkbrenner/HVRawConnectorPHP/master/LICENSE.txt).


Sponsor
-------
[bio.logis](https://www.biologis.com) offers users of
[pgsbox.de](https://pgsbox.de) to upload diagnostic reports to HealthVault.
