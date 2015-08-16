# StarMadeVoteRewards
StarMadeVoteRewards is a small project to provide rewards for playes in the game StarMade when your server is voted for by a player on the server listing site starmade-servers.com

## About ##
starmade-servers.com offers a nice little API to get the list of voters for a StarMade server, and also to do neat things like mark a vote as claimed. This makes it pretty trivial to be able to reward players for voting for your listing.


I spent a few minutes hacking together some PHP to do this, and then added a crontab to run it every 5 minutes - so when someone votes for Mikeland StarMade, if are currently logged in, or if they log into the server before the next voting day begins, they get rewarded with 1,000,000 in-game credits.


The reward you can issue does not have to be credits, basically any console command can be issued (some more sadistic people might choose to ban people for voting) - but I settled on in-game credits. I also decided to add a chat broadcast, so when the player does get his credits, it's broadcast to the server to entice more people to vote.


The php is fairly straightforward, but I had to put a few twists and turns in because of the limited API options exposed to me by starmade-servers.com doesn't just give me a list of people who voted in the voting day (you can only vote once per day per player, and thus can only claim one vote per day per player). I can get a list of ALL votes, and I can check with their API if a player has voted in the last voting day - so I have to make the first API query to get the list of all possible voters, then make a separate API call for each of those voters to see if they have voted in the last voting day. I would have preferred to have an option to make just one API call to get all unclaimed votes which are eligible to claim, but we can't always have what we want.
## Prerequisites ##

## Installation ##
Place the rewardvotes.php in the same folder as your StarMade server

## Configuration ##
Edit rewardvotes.php - at the top of this PHP script you will see the following:

```
   $adminPassword = "SETTHISTOBEYOURS"; // this is the Super admin password from the StarMade server.cfg
   $rewardamount = 1000000;
   $serverKey = 'SETTHISTOBEYOURS'; // This is the API Key issued to your listing by starmade-servers.com
   $javapath = "/usr/share/java/jre1.8.0/bin";  // if you plan on calling this from a crontab, you must fully path all file locations
   $starnetpath = "/starmade/StarMade";
```
Edit the above values to be the values for your server.

The script can then be run manually using the command:

```
/usr/bin/php rewardvotes.php
```

You should see the output to the console as it progresses through.
I wanted to run this every 5 minutes, so I added a crontab to do this (crontab -e at the shell) - I also wanted to log the output to a log file so I could ensure it all was working as expected.

```
*/5 * * * * /usr/bin/php /starmade/StarMade/rewardvotes.php 2>&1 >> /starmade/StarMade/rewardvotes.log
```