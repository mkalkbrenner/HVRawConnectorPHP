HVRawConnectorPHP
=================

HVRawConnectorPHP is simple PHP library to connect to
[Microsoft HealthVault](https://www.healthvault.com/). It enables PHP developers
to send and receive raw XML documents to and from HealthVault and hides the
complicated part of the protocol, espacially the various hashing and signing parts.

If you're interested in a high level API to build HealthVault applications with
PHP, have a look at
[HVClientLibPHP](https://github.com/mkalkbrenner/HVClientLibPHP/) which aims to
provide a nice object oriented programming interface comparable to Microsoft's
HealthVault SDK.


Installation
------------

HVRawConnectorPHP depends on some PHP libraries. If you simply use the latest
development version of HVRawConnectorPHP from github you have to ensure that
all of them are installed. You can use pear to do so:

    pear channel-discover pear.querypath.org
    pear install querypath/QueryPath
    pear install Log
    pear install Net_URL2

HVRawConnectorPHP itself could be installed by pear, too. In that case all the
dependencies mentioned above will be installed automatically:

    pear channel-discover pear.biologis.com
    pear channel-discover pear.querypath.org
    pear install biologis/HVRawConnector

This method will install HVRawConnectorPHP as a library, but without the
available demo application.


Usage
-----

Some examples will follow later.

Meanwhile you can have a look at the demo_app source code.


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
