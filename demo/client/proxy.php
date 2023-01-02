<?php
require_once __DIR__ . "/_prepend.php";

output('<html lang="en">
<head><title>xmlrpc - Proxy demo</title></head>
<body>
<h1>proxy demo</h1>
<h2>Query server using a "proxy" object</h2>
<h3>The code demonstrates usage for the terminally lazy. For a more complete proxy, look at the Wrapper class</h3>
<p>You can see the source to this page here: <a href="proxy.php?showSource=1">proxy.php</a></p>
');

class PhpXmlRpcProxy
{
    protected $client;
    protected $prefix;
    protected $encodingOptions = array();

    public function __construct(PhpXmlRpc\Client $client, $prefix = 'examples.', $encodingOptions = array())
    {
        $this->client = $client;
        $this->prefix = $prefix;
        $this->encodingOptions = $encodingOptions;
    }

    /**
     * Translates any method call to an xmlrpc call.
     *
     * @author Toth Istvan
     *
     * @param string $name remote function name. Will be prefixed
     * @param array $arguments
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function __call($name, $arguments)
    {
        $encoder = new PhpXmlRpc\Encoder();
        $valueArray = array();
        foreach ($arguments as $parameter) {
            $valueArray[] = $encoder->encode($parameter, $this->encodingOptions);
        }

        // just in case this was set to something else
        $this->client->return_type = 'phpvals';

        $resp = $this->client->send(new PhpXmlRpc\Request($this->prefix.$name, $valueArray));

        if ($resp->faultCode()) {
            throw new Exception($resp->faultString(), $resp->faultCode());
        } else {
            return $resp->value();
        }
    }

    /**
     * In case the remote method name has characters which are not valid as php method names, use this.
     *
     * @param string $name remote function name. Will be prefixed
     * @param array $arguments
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function call($name, $arguments)
    {
        return $this->__call($name, $arguments);
    }
}

$stateNo = rand(1, 51);
$proxy = new PhpXmlRpcProxy(new PhpXmlRpc\Client(XMLRPCSERVER));
$stateName = $proxy->getStateName($stateNo);

output("State $stateNo is ".htmlspecialchars($stateName));

output("</body></html>\n");
