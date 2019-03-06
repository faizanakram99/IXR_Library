<?php
/**
 * IXR - The Inutio XML-RPC Library - (c) Incutio Ltd 2002
 * Version 1.61 - Simon Willison, 11th July 2003 (htmlentities -> htmlspecialchars)
 * Site:   http://scripts.incutio.com/xmlrpc/
 * Manual: http://scripts.incutio.com/xmlrpc/manual.php
 * Made available under the Artistic License: http://www.opensource.org/licenses/artistic-license.php.
 */
class IXR_Server
{
    public $data;
    public $callbacks = [];
    public $message;
    public $capabilities;

    public function __construct($callbacks = false, $data = false)
    {
        $this->setCapabilities();
        if ($callbacks) {
            $this->callbacks = $callbacks;
        }
        $this->setCallbacks();
        $this->serve($data);
    }

    public function serve($data = false)
    {
        if (!$data) {
            global $HTTP_RAW_POST_DATA;
            if (!$HTTP_RAW_POST_DATA) {
                die('XML-RPC server accepts POST requests only.');
            }
            $data = $HTTP_RAW_POST_DATA;
        }
        $this->message = new IXR_Message($data);
        if (!$this->message->parse()) {
            $this->error(-32700, 'parse error. not well formed');
        }
        if ('methodCall' != $this->message->messageType) {
            $this->error(-32600, 'server error. invalid xml-rpc. not conforming to spec. Request must be a methodCall');
        }
        $result = $this->call($this->message->methodName, $this->message->params);
        // Is the result an error?
        if (is_a($result, 'IXR_Error')) {
            $this->error($result);
        }
        // Encode the result
        $r = new IXR_Value($result);
        $resultxml = $r->getXml();
        // Create the XML
        $xml = <<<EOD
<methodResponse>
  <params>
    <param>
      <value>
        $resultxml
      </value>
    </param>
  </params>
</methodResponse>

EOD;
        // Send it
        $this->output($xml);
    }

    public function call($methodname, $args)
    {
        if (!$this->hasMethod($methodname)) {
            return new IXR_Error(-32601, 'server error. requested method '.$methodname.' does not exist.');
        }
        $method = $this->callbacks[$methodname];
        // Perform the callback and send the response
        if (1 == count($args)) {
            // If only one paramater just send that instead of the whole array
            $args = $args[0];
        }
        // Are we dealing with a function or a method?
        if ('this:' == substr($method, 0, 5)) {
            // It's a class method - check it exists
            $method = substr($method, 5);
            if (!method_exists($this, $method)) {
                return new IXR_Error(-32601, 'server error. requested class method "'.$method.'" does not exist.');
            }
            // Call the method
            $result = $this->$method($args);
        } else {
            // It's a function - does it exist?
            if (!function_exists($method)) {
                return new IXR_Error(-32601, 'server error. requested function "'.$method.'" does not exist.');
            }
            // Call the function
            $result = $method($args);
        }

        return $result;
    }

    public function error($error, $message = false)
    {
        // Accepts either an error object or an error code and message
        if ($message && !is_object($error)) {
            $error = new IXR_Error($error, $message);
        }
        $this->output($error->getXml());
    }

    public function output($xml)
    {
        $xml = '<?xml version="1.0"?>'."\n".$xml;
        $length = strlen($xml);
        header('Connection: close');
        header('Content-Length: '.$length);
        header('Content-Type: text/xml');
        header('Date: '.date('r'));
        echo $xml;
        exit;
    }

    public function hasMethod($method)
    {
        return in_array($method, array_keys($this->callbacks));
    }

    public function setCapabilities()
    {
        // Initialises capabilities array
        $this->capabilities = [
            'xmlrpc' => [
                'specUrl' => 'http://www.xmlrpc.com/spec',
                'specVersion' => 1,
            ],
            'faults_interop' => [
                'specUrl' => 'http://xmlrpc-epi.sourceforge.net/specs/rfc.fault_codes.php',
                'specVersion' => 20010516,
            ],
            'system.multicall' => [
                'specUrl' => 'http://www.xmlrpc.com/discuss/msgReader$1208',
                'specVersion' => 1,
            ],
        ];
    }

    public function getCapabilities($args)
    {
        return $this->capabilities;
    }

    public function setCallbacks()
    {
        $this->callbacks['system.getCapabilities'] = 'this:getCapabilities';
        $this->callbacks['system.listMethods'] = 'this:listMethods';
        $this->callbacks['system.multicall'] = 'this:multiCall';
    }

    public function listMethods($args)
    {
        // Returns a list of methods - uses array_reverse to ensure user defined
        // methods are listed before server defined methods
        return array_reverse(array_keys($this->callbacks));
    }

    public function multiCall($methodcalls)
    {
        // See http://www.xmlrpc.com/discuss/msgReader$1208
        $return = [];
        foreach ($methodcalls as $call) {
            $method = $call['methodName'];
            $params = $call['params'];
            if ('system.multicall' == $method) {
                $result = new IXR_Error(-32600, 'Recursive calls to system.multicall are forbidden');
            } else {
                $result = $this->call($method, $params);
            }
            if (is_a($result, 'IXR_Error')) {
                $return[] = [
                    'faultCode' => $result->code,
                    'faultString' => $result->message,
                ];
            } else {
                $return[] = [$result];
            }
        }

        return $return;
    }
}
