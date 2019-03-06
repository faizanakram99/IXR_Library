# Incutio XML-RPC library (IXR)

**Note**: _This is a fully backward compatible, PHP 7 compatible, [psr-4](https://www.php-fig.org/psr/psr-4/) compliant fork of the original Incutio XML-RPC library (IXR) SVN repo hosted on <https://code.google.com/p/php-ixr/>. All classes of this package are added to global namespace (just for backward compatibility). You can easily replace original IXR_Library with this package with no changes to your code (except removing `require 'IXR_Library.php'` as classes will be autoloaded with composer)._


All credits go to Incutio.

**Docs and Homepage:** <http://scripts.incutio.com/xmlrpc/>

# Introduction

The Incutio XML-RPC library (IXR) is designed primarily for ease of use. It incorporates both client and server classes, and is designed to hide as much of the workings of XML-RPC from the user as possible. A key feature of the library is automatic type conversion from PHP types to XML-RPC types and vice versa. This should enable developers to write web services with very little knowledge of the underlying XML-RPC standard.

Don't however be fooled by it's simple surface. The library includes a wide variety of additional XML-RPC specifications and has all of the features required for serious web service implementations.

# Background / History

The original XML-RPC library was developed back in 2002 and updated through 2003 by Incutio for a number of projects the company was working on at the time. It has become fairly dated but is still used extensively by a wide range of commercial and open-source projects.

# Installation using [composer](http://getcomposer.org/)
1. Download [composer](http://getcomposer.org/)
2. Run `composer require faizan/ixr-library` from root of your project
3. require `vendor/autoload.php`
4. Use classes of IXR_Library (Fully backward compatible) as you used to.
