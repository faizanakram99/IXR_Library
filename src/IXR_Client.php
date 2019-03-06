<?php
/**
 * IXR - The Inutio XML-RPC Library - (c) Incutio Ltd 2002
 * Version 1.61 - Simon Willison, 11th July 2003 (htmlentities -> htmlspecialchars)
 * Site:   http://scripts.incutio.com/xmlrpc/
 * Manual: http://scripts.incutio.com/xmlrpc/manual.php
 * Made available under the Artistic License: http://www.opensource.org/licenses/artistic-license.php.
 */
class IXR_Client
{
    public $server;
    public $port;
    public $scheme;
    public $path;
    public $useragent;
    public $response;
    public $message = false;
    public $debug = false;
    // Storage place for an error message
    public $error = false;

    public function __construct($server, $path = false, $port = 80)
    {
        if (!$path) {
            // Assume we have been given a URL instead
            $bits = parse_url($server);
            $this->server = $bits['host'];
            $this->scheme = $bits['scheme'];
            $this->port = isset($bits['port']) ? $bits['port'] : ('https' == $this->scheme ? 443 : 80);
            $this->path = isset($bits['path']) ? $bits['path'] : '/';
            // Make absolutely sure we have a path
            if (!$this->path) {
                $this->path = '/';
            }
        } else {
            $this->server = $server;
            $this->path = $path;
            $this->port = $port;
        }
        $this->useragent = 'The Incutio XML-RPC PHP Library';
    }

    public function query()
    {
        $args = func_get_args();
        $method = array_shift($args);
        $request = new IXR_Request($method, $args);
        $length = $request->getLength();
        $xml = $request->getXml();
        $r = "\r\n";
        $request = "POST {$this->path} HTTP/1.0$r";
        $request .= "Host: {$this->server}$r";
        $request .= "Content-Type: text/xml$r";
        $request .= "User-Agent: {$this->useragent}$r";
        $request .= "Content-length: {$length}$r$r";
        $request .= $xml;
        // Now send the request
        if ($this->debug) {
            echo '<pre>'.htmlspecialchars($request)."\n</pre>\n\n";
        }
        if ('https' == $this->scheme) {
            $contextoptions = [
                'ssl' => [
                    'verify_peer' => true,
                    'verify_depth' => 5,
                    'peer_name' => $this->server,
                ],
            ];
            $fp = stream_socket_client('ssl://'.$this->server.':'.$this->port, $errno, $errstr, 10, STREAM_CLIENT_CONNECT, stream_context_create($contextoptions));
        } else {
            $fp = @fsockopen($this->server, $this->port);
        }
        if (!$fp) {
            $this->error = new IXR_Error(-32300, 'transport error - could not open socket');

            return false;
        }
        fputs($fp, $request);
        $contents = '';
        $gotFirstLine = false;
        $gettingHeaders = true;
        while (!feof($fp)) {
            $line = fgets($fp, 50000);
            if (!$gotFirstLine) {
                // Check line for '200'
                if (false === strstr($line, '200')) {
                    $this->error = new IXR_Error(-32300, 'transport error - HTTP status code was not 200');

                    return false;
                }
                $gotFirstLine = true;
            }
            if ('' == trim($line)) {
                $gettingHeaders = false;
            }
            if (!$gettingHeaders) {
                $contents .= trim($line)."\n";
            }
        }
        if ($this->debug) {
            echo '<pre>'.htmlspecialchars($contents)."\n</pre>\n\n";
        }
        // Now parse what we've got back
        $this->message = new IXR_Message($contents);
        if (!$this->message->parse()) {
            // XML error
            $this->error = new IXR_Error(-32700, 'parse error. not well formed');

            return false;
        }
        // Is the message a fault?
        if ('fault' == $this->message->messageType) {
            $this->error = new IXR_Error($this->message->faultCode, $this->message->faultString);

            return false;
        }
        // Message must be OK
        return true;
    }

    public function getResponse()
    {
        // methodResponses can only have one param - return that
        return $this->message->params[0];
    }

    public function isError()
    {
        return is_object($this->error);
    }

    public function getErrorCode()
    {
        return $this->error->code;
    }

    public function getErrorMessage()
    {
        return $this->error->message;
    }
}
