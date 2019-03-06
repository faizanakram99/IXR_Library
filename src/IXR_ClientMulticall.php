<?php
/**
 * IXR - The Inutio XML-RPC Library - (c) Incutio Ltd 2002
 * Version 1.61 - Simon Willison, 11th July 2003 (htmlentities -> htmlspecialchars)
 * Site:   http://scripts.incutio.com/xmlrpc/
 * Manual: http://scripts.incutio.com/xmlrpc/manual.php
 * Made available under the Artistic License: http://www.opensource.org/licenses/artistic-license.php.
 */
class IXR_ClientMulticall extends IXR_Client
{
    public $calls = [];

    public function __construct($server, $path = false, $port = 80)
    {
        parent::__construct($server, $path, $port);
        $this->useragent = 'The Incutio XML-RPC PHP Library (multicall client)';
    }

    public function addCall()
    {
        $args = func_get_args();
        $methodName = array_shift($args);
        $struct = [
            'methodName' => $methodName,
            'params' => $args,
        ];
        $this->calls[] = $struct;
    }

    public function query()
    {
        // Prepare multicall, then call the parent::query() method
        return parent::query('system.multicall', $this->calls);
    }
}
