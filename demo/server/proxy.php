<?php
/**
 * XMLRPC server acting as proxy for requests to other servers
 * (useful e.g. for ajax-originated calls that can only connect back to the originating server).
 * NB: this is an OPEN RELAY. It is meant as a demo, not to be used in production!
 * For an example of a transparent reverse-proxy, see the ReverseProxy class in package phpxmlrpc/extras.
 *
 * @author Gaetano Giunta
 * @copyright (C) 2006-2023 G. Giunta
 * @license code licensed under the BSD License: see file license.txt
 */

require_once __DIR__ . "/_prepend.php";

// *** NB: WE BLOCK THIS FROM RUNNING BY DEFAULT IN CASE ACCESS IS GRANTED TO IT IN PRODUCTION BY MISTAKE ***
// Comment out the following safeguard if you want to use it as is, but remember: this is an open relay !!!
if (!defined('TESTMODE')) {
    die("Server disabled by default for safety");
}

use PhpXmlRpc\Client;
use PhpXmlRpc\Encoder;
use PhpXmlRpc\Request;
use PhpXmlRpc\Server;

/**
 * Forward an xmlrpc request to another server, and return to client the response received.
 *
 * @param PhpXmlRpc\Request $req (see method docs below for a description of the expected parameters)
 * @return PhpXmlRpc\Response
 */
function forward_request($req)
{
    $encoder = new Encoder();

    // create client
    $timeout = 0;
    $url = $encoder->decode($req->getParam(0));
    // NB: here we should validate the received url, using f.e. a whitelist...
    $client = new Client($url);

    if ($req->getNumParams() > 3) {
        // we have to set some options onto the client.
        // Note that if we do not untaint the received values, warnings might be generated...
        $options = $encoder->decode($req->getParam(3));
        foreach ($options as $key => $val) {
            switch ($key) {
                case 'Cookie':
                    /// @todo add support for this if needed
                    break;
                case 'Credentials':
                    /// @todo add support for this as well if needed
                    break;
                case 'RequestCompression':
                    $client->setRequestCompression($val);
                    break;
                case 'SSLVerifyHost':
                    $client->setSSLVerifyHost($val);
                    break;
                case 'SSLVerifyPeer':
                    $client->setSSLVerifyPeer($val);
                    break;
                case 'Timeout':
                    $timeout = (integer)$val;
                    break;
            } // switch
        }
    }

    // build call for remote server
    /// @todo find a way to forward client info (such as IP) to server, either
    ///       - as xml comments in the payload, or
    ///       - using std http header conventions, such as X-forwarded-for...
    $reqMethod = $req->getParam(1)->scalarval();
    $pars = $req->getParam(2);
    $req = new Request($reqMethod);
    foreach ($pars as $par) {
        $req->addParam($par);
    }

    // add debug info into response we give back to caller
    Server::xmlrpc_debugmsg("Sending to server $url the payload: " . $req->serialize());

    return $client->send($req, $timeout);
}

// Run the server
// NB: take care not to output anything else after this call, as it will mess up the responses and it will be hard to
// debug. In case you have to do so, at least re-emit a correct Content-Length http header (requires output buffering)
$server = new Server(
    array(
        'xmlrpcproxy.call' => array(
            'function' => 'forward_request',
            'signature' => array(
                array('mixed', 'string', 'string', 'array'),
                array('mixed', 'string', 'string', 'array', 'struct'),
            ),
            'docstring' => 'forwards xmlrpc calls to remote servers. Returns remote method\'s response. Accepts params: remote server url (might include basic auth credentials), method name, array of params, and (optionally) a struct containing call options',
        ),
    )
);
