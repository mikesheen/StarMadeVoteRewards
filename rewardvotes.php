<?php
   // rewardvote.php
   // Author: Mike Sheen (mike@sheen.id.au)
   // Date: 18 July 2014

   echo("==== Checking for votes " . date('l jS \of F Y h:i:s A') . " ====");

   // You need the following configurations set in your server.cfg for StarMade:
   // SUPER_ADMIN_PASSWORD_USE = true //Enable super admin for this server
   // SUPER_ADMIN_PASSWORD = SETTHISTOBEYOURS //Super admin password for this server

   $adminPassword = "SETTHISTOBEYOURS"; // this is the Super admin password from the StarMade server.cfg
   $rewardamount = 1000000;
   $serverKey = 'SETTHISTOBEYOURS'; // This is the API Key issued to your listing by starmade-servers.com
   $javapath = "/usr/share/java/jre1.8.0/bin";  // if you plan on calling this from a crontab, you must fully path all file locations
   $starnetpath = "/starmadenew/StarMade";

   //First we get the json back from the server listing page for Mikeland the votes
   $url = "http://starmade-servers.com/api/?object=servers&element=votes&key=" . $serverKey;

   $options = array(
         'http' => array(
         'header'  => "Content-type: application/json\r\n",
         'method'  => 'GET',
         ),
   );

   $context = stream_context_create($options);
   $contents = file_get_contents($url, false, $context);
   $result = json_decode($contents, true);
   $voters = array();

   //Now we process the results
   if (sizeof($result['votes']) > 0) {
      //loop through each vote
      foreach ($result['votes'] as $vote) {
         //we're only interested in votes not claimed
         if ($vote['claimed'] == 0) {
            array_push($voters, $vote['nickname']);
         }
      }
      // Now make the  list unique
      $uniqueVoters = array_unique($voters, SORT_STRING);

      foreach ($uniqueVoters as $vote) {
        echo("\nUnclaimed vote found for " . $vote);

        //now we need to find out if it was a vote for last 24 hours.  We need to do this, because we can't set a vote as claimed unless it was within the last 24 hours (starmade-servers.com says so).
        $url = "http://starmade-servers.com/api/?object=votes&element=claim&key=" . $serverKey . "&username=" . $vote;

        $options = array(
                  'http' => array(
                    'header'  => "Content-type: application/json\r\n",
                    'method'  => 'GET',
                  ),
        );
        $context = stream_context_create($options);
        $contents = file_get_contents($url, false, $context);

        // A return value of "1" means they've voted in the last day, but not claimed it yet - "2" means voted in the last day but already claimed, "0" means not voted in the last day - we're only interested in the "1" values
        if ($contents == "1") {
           //run the command to give the credit
           $output = "";
           $returncode = 0;
           exec($javapath . '/java -jar ' . $starnetpath . '/StarNet.jar 127.0.0.1:4242 ' . $adminPassword . ' /give_credits ' . $vote . ' ' . $rewardamount . ' 2>&1', $output, $returncode);

           //check the results - if we were successful, send a message back to the listing site to mark the vote as claimed
            if ($returncode == 0) {
               $errorPattern = "[ERROR] Player not found:";
               $matchfound = false;

               foreach ($output as $outputline) {
                  if (stripos($outputline, $errorPattern) !== false) {
                    $matchfound = 1;
                    break;
                 }
               }

               //if we didn't find the 'player not found' in the output, assume we gave them the credits - so now tell starmade-servers.com that that vote has been claimed.
               if ($matchfound == false) {
                  echo("... Gave player credits ok, setting vote as claimed");
                  $url = "http://starmade-servers.com/api/?action=post&object=votes&element=claim&key=" . $serverKey . "&username=" . $vote;

                  $options = array(
                     'http' => array(
                     'header'  => "Content-type: application/json\r\n",
                     'method'  => 'POST'
                     ),
                  );
                  $context = stream_context_create($options);
                  $result = file_get_contents($url, false, $context);

                  //page will return 1 if everything was ok, otherwise 0 - not sure what we can do if we gave the credits but couldn't mark it as claimed - try to take the credits off them ? lol
                  echo("... broadcasting the transaction");
                  exec($javapath . '/java -jar ' . $starnetpath . '/StarNet.jar 127.0.0.1:4242 ' . $adminPassword . ' /chat gave ' . $vote . ' ' . $rewardamount . ' credits for voting for Mikeland on starmade-servers.com! 2>&1', $output, $returncode);
               }
               else {
                  echo("...Player was not online, not setting vote as claimed");
               }
            }
        } else {
           echo("...Unclaimed vote was older than 24 hours, ignoring");
        }
      }
   }
echo("\n==== Finished ====");
echo("\n");
?>