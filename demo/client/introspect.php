<html>
<head><title>xmlrpc - Introspect demo</title></head>
<body>
<h1>Introspect demo</h1>
<h2>Query server for available methods and their description</h2>
<h3>The code demonstrates usage of multicall and introspection methods</h3>
<?php

include_once __DIR__ . "/../../src/Autoloader.php";
PhpXmlRpc\Autoloader::register();

function display_error($r)
{
    print "An error occurred: ";
    print "Code: " . $r->faultCode()
        . " Reason: '" . $r->faultString() . "'<br/>";
}
// 'new style' client constructor
$client = new PhpXmlRpc\Client("http://phpxmlrpc.sourceforge.net/server.php");
print "<h3>methods available at http://" . $client->server . $client->path . "</h3>\n";
$req = new PhpXmlRpc\Request('system.listMethods');
$resp = $client->send($req);
if ($resp->faultCode()) {
    display_error($resp);
} else {
    $v = $resp->value();
    for ($i = 0; $i < $v->arraysize(); $i++) {
        $mname = $v->arraymem($i);
        print "<h4>" . $mname->scalarval() . "</h4>\n";
        // build messages first, add params later
        $m1 = new PhpXmlRpc\Request('system.methodHelp');
        $m2 = new PhpXmlRpc\Request('system.methodSignature');
        $val = new PhpXmlRpc\Value($mname->scalarval(), "string");
        $m1->addParam($val);
        $m2->addParam($val);
        // send multiple messages in one pass.
        // If server does not support multicall, client will fall back to 2 separate calls
        $ms = array($m1, $m2);
        $rs = $client->send($ms);
        if ($rs[0]->faultCode()) {
            display_error($rs[0]);
        } else {
            $val = $rs[0]->value();
            $txt = $val->scalarval();
            if ($txt != "") {
                print "<h4>Documentation</h4><p>${txt}</p>\n";
            } else {
                print "<p>No documentation available.</p>\n";
            }
        }
        if ($rs[1]->faultCode()) {
            display_error($rs[1]);
        } else {
            print "<h4>Signature</h4><p>\n";
            $val = $rs[1]->value();
            if ($val->kindOf() == "array") {
                for ($j = 0; $j < $val->arraysize(); $j++) {
                    $x = $val->arraymem($j);
                    $ret = $x->arraymem(0);
                    print "<code>" . $ret->scalarval() . " "
                        . $mname->scalarval() . "(";
                    if ($x->arraysize() > 1) {
                        for ($k = 1; $k < $x->arraysize(); $k++) {
                            $y = $x->arraymem($k);
                            print $y->scalarval();
                            if ($k < $x->arraysize() - 1) {
                                print ", ";
                            }
                        }
                    }
                    print ")</code><br/>\n";
                }
            } else {
                print "Signature unknown\n";
            }
            print "</p>\n";
        }
    }
}
?>
</body>
</html>
