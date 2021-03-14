Retrieved via composer on 12-3-2020



For use, see: https://docs.signalwire.com/topics/laml-api/?php#api-reference-messages-the-message-object



For php, to use, gotta do this:

<?php
  require 'path-to/vendor/autoload.php';
?>



also...


<?php
  use SignalWire\Rest\Client;

  $client = new Client('your-project', 'your-token', array("signalwireSpaceUrl" => "example.signalwire.com"));

  // You can then use $client to make calls, send messages, and more.
?>


---------------------------------------

See: https://docs.signalwire.com/topics/laml-api/?php#api-reference-messages

Sending an SMS message:

<?php
  use SignalWire\Rest\Client;

  $client = new Client('YourProjectID', 'YourAuthToken', array("signalwireSpaceUrl" => "example.signalwire.com"));

  $message = $client->messages
                    ->create("+15557654321", // to
                             array("from" => "+15551234567", "body" => "Hello World!")
                    );

  print($message->sid);
?>