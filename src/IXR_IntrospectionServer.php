<?php

/**
 * IXR - The Inutio XML-RPC Library - (c) Incutio Ltd 2002
 * Version 1.61 - Simon Willison, 11th July 2003 (htmlentities -> htmlspecialchars)
 * Site:   http://scripts.incutio.com/xmlrpc/
 * Manual: http://scripts.incutio.com/xmlrpc/manual.php
 * Made available under the Artistic License: http://www.opensource.org/licenses/artistic-license.php.
 */
class IXR_IntrospectionServer extends IXR_Server
{
    public $signatures;
    public $help;

    public function __construct()
    {
        $this->setCallbacks();
        $this->setCapabilities();
        $this->capabilities['introspection'] = [
            'specUrl' => 'http://xmlrpc.usefulinc.com/doc/reserved.html',
            'specVersion' => 1,
        ];
        $this->addCallback(
            'system.methodSignature',
            'this:methodSignature',
            ['array', 'string'],
            'Returns an array describing the return type and required parameters of a method'
        );
        $this->addCallback(
            'system.getCapabilities',
            'this:getCapabilities',
            ['struct'],
            'Returns a struct describing the XML-RPC specifications supported by this server'
        );
        $this->addCallback(
            'system.listMethods',
            'this:listMethods',
            ['array'],
            'Returns an array of available methods on this server'
        );
        $this->addCallback(
            'system.methodHelp',
            'this:methodHelp',
            ['string', 'string'],
            'Returns a documentation string for the specified method'
        );
    }

    public function addCallback($method, $callback, $args, $help)
    {
        $this->callbacks[$method] = $callback;
        $this->signatures[$method] = $args;
        $this->help[$method] = $help;
    }

    public function call($methodname, $args)
    {
        // Make sure it's in an array
        if ($args && !is_array($args)) {
            $args = [$args];
        }
        // Over-rides default call method, adds signature check
        if (!$this->hasMethod($methodname)) {
            return new IXR_Error(-32601, 'server error. requested method "'.$this->message->methodName.'" not specified.');
        }
        $method = $this->callbacks[$methodname];
        $signature = $this->signatures[$methodname];
        $returnType = array_shift($signature);
        // Check the number of arguments
        if (count($args) != count($signature)) {
            // print 'Num of args: '.count($args).' Num in signature: '.count($signature);
            return new IXR_Error(-32602, 'server error. wrong number of method parameters');
        }
        // Check the argument types
        $ok = true;
        $argsbackup = $args;
        for ($i = 0, $j = count($args); $i < $j; ++$i) {
            $arg = array_shift($args);
            $type = array_shift($signature);
            switch ($type) {
                case 'int':
                case 'i4':
                    if (is_array($arg) || !is_int($arg)) {
                        $ok = false;
                    }
                    break;
                case 'base64':
                case 'string':
                    if (!is_string($arg)) {
                        $ok = false;
                    }
                    break;
                case 'boolean':
                    if (false !== $arg && true !== $arg) {
                        $ok = false;
                    }
                    break;
                case 'float':
                case 'double':
                    if (!is_float($arg)) {
                        $ok = false;
                    }
                    break;
                case 'date':
                case 'dateTime.iso8601':
                    if (!is_a($arg, 'IXR_Date')) {
                        $ok = false;
                    }
                    break;
            }
            if (!$ok) {
                return new IXR_Error(-32602, 'server error. invalid method parameters');
            }
        }
        // It passed the test - run the "real" method call
        return parent::call($methodname, $argsbackup);
    }

    public function methodSignature($method)
    {
        if (!$this->hasMethod($method)) {
            return new IXR_Error(-32601, 'server error. requested method "'.$method.'" not specified.');
        }
        // We should be returning an array of types
        $types = $this->signatures[$method];
        $return = [];
        foreach ($types as $type) {
            switch ($type) {
                case 'string':
                    $return[] = 'string';
                    break;
                case 'int':
                case 'i4':
                    $return[] = 42;
                    break;
                case 'double':
                    $return[] = 3.1415;
                    break;
                case 'dateTime.iso8601':
                    $return[] = new IXR_Date(time());
                    break;
                case 'boolean':
                    $return[] = true;
                    break;
                case 'base64':
                    $return[] = new IXR_Base64('base64');
                    break;
                case 'array':
                    $return[] = ['array'];
                    break;
                case 'struct':
                    $return[] = ['struct' => 'struct'];
                    break;
            }
        }

        return $return;
    }

    public function methodHelp($method)
    {
        return $this->help[$method];
    }
}
