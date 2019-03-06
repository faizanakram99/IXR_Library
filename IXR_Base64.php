<?php
/**
 * IXR - The Inutio XML-RPC Library - (c) Incutio Ltd 2002
 * Version 1.61 - Simon Willison, 11th July 2003 (htmlentities -> htmlspecialchars)
 * Site:   http://scripts.incutio.com/xmlrpc/
 * Manual: http://scripts.incutio.com/xmlrpc/manual.php
 * Made available under the Artistic License: http://www.opensource.org/licenses/artistic-license.php.
 */
class IXR_Base64
{
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getXml()
    {
        return '<base64>'.base64_encode($this->data).'</base64>';
    }
}
