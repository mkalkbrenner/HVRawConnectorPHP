HVRawConnectorPHP
=================

Simple PHP library to connect
[Microsoft HealthVault](https://www.healthvault.com/).


Requirements
------------

HVRawConnectorPHP depends on some PHP libraries. All of them can be installed
with pear:

    pear channel-discover pear.querypath.org
    pear install querypath/QueryPath
    pear install Log
    pear install Net_URL2

Installation and Usage
----------------------

TODO

Meanwhile you can have a look at the demo_app source code.


Demo
----

The demo_app included in this repository queries a user's HealthVault record
for all "[Things](http://developer.healthvault.com/pages/types/types.aspx)" and
dumps the content.

Simply put the HVRawConnectorPHP folder on a webserver and access
"demo_app/index.php".


Licence
-------

[GPLv2](https://raw.github.com/mkalkbrenner/HVRawConnectorPHP/master/LICENSE.txt).


Sponsor
-------
[bio.logis](https://www.biologis.com) offers users of
[pgsbox.de](https://pgsbox.de) to upload clinical reports to HealthVault.
