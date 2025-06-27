<?PHP
error_reporting(0);
header("Content-type: text/html; charset=utf-8");
 
$detailed = false;
$graph_load = false;
$simple_load = false;
$simple_count = false;
$stream_count = false;
$geo_count = false;
$count = false;
$load = false;
$form = true;
 
if (isset($_GET['server'])) { $repeater = $_GET['server']; $form = false;} 
 
if (isset($_GET['mode'])) { 
    switch ($_GET['mode']) {
        case "detailed":  $detailed = true; $count=true; break;
        case "graph_load" : $graph_load = true; $load = true; break;
        case "simple_load" : $simple_load = true; $load = true; break;
        case "simple_count" : $simple_count = true; $load = true; break;
        case "geo_count" : $geo_count = true; $count = true; break;
        case "stream_count" : $stream_count = true; $count = true; break;
    }  
    $form = false;
} else { $form = true; }
if (isset($_GET['port'])) { $adminport = $_GET['port'];} else { $adminport='8086'; }
 
if ($form) {
 
?>
<HTML>
 <HEAD>
  <TITLE>Single-Server Metrics</TITLE>
 </HEAD>
 <BODY>
  <H3>Single-Server Stats Gathering</H3>
  <FORM ACTION="#" METHOD="GET">
   <P>Server Address:<BR>
   <INPUT TYPE="text" size="15" value="<?=$repeater;?>" name="server"></P>
   <P>Admin Port<BR>
   <INPUT TYPE="text" size="15" value="8086" name="port"></P>
   <P>Data to display: <BR>
   <INPUT TYPE="radio" name="mode" value="detailed">Detailed Count</INPUT><BR>
   <INPUT TYPE="radio" name="mode" value="graph_load">Load Graph</INPUT><BR>
   <INPUT TYPE="radio" name="mode" value="simple_load">Load in Mbps</INPUT><BR>
   <INPUT TYPE="radio" name="mode" value="simple_count">Simple Count</INPUT><BR>
   <INPUT TYPE="radio" name="mode" value="geo_count">Geographical Count</INPUT><BR>
   <INPUT TYPE="radio" name="mode" value="stream_count">Stream Count</INPUT><BR>
   <BR>
   <INPUT TYPE="submit" Value="Show Me The Data">
  </FORM>
 </BODY>
</HTML>
 
<?php 
 
} else {
 
 
// Iterate through IP address array
// initialize counters
$agent['iPhone']=0;
$agent['iPad']=0;
$agent['iPod']=0;
$agent['stagefright']=0;
$agent['Roku']=0;
$agent['Flash']=0;
 
$proto['RTMP']=0;
$proto['RTSP']=0;
$proto['HLS']=0;
$proto['2']=0;
$proto['3']=0;
$proto['4']=0;
$server_bwin = 0;
$server_bwout = 0;
$server_conns = 0;
 
 
if ($graph_load) { echo "<H3>Server Load</H3>"; }
 
    $filter = array(
            "Name" => 'ip-address',
            "Value" => $repeater
            );
    $opts = array (
            "Filter" => $filter
            );
     
    if($load) { // compute load per server
 
                $repeaterurl = "http://$repeater:$adminport/connectioncounts";
                $repeaterxml = simplexml_load_file($repeaterurl);
 
                foreach ($repeaterxml->VHost as $vhost) {
                        foreach ($vhost->Application as $app) { // parse bandwidth usage by app in case we want it. 
                $appname = (string)$app->Name;
                if ($app->Status == "Loaded") {
                    $inbytes = (string)$app->MessagesInBytesRate;
                    $outbytes = (string)$app->MessagesOutBytesRate;
                    $bwin[$appname] = $inbytes / 131072; // Convert Bps to Mbps
                    $bwout[$appname] = $outbytes / 131027; // Convert Bps to Mbps
                } 
            } // End App Loop
        } // End VHost Loop
        $server_bwin = (string)$repeaterxml->MessagesInBytesRate / 131072; // Convert Bps to Mbps
        $server_bwout = (string)$repeaterxml->MessagesOutBytesRate / 131072; // Convert Bps to Mbps
        $server_conns = $repeaterxml->ConnectionsCurrent;
 
        if ($graph_load) {
            $server_load = $server_bwout / 1024 * 100; // convert to percentage
 
            $load_string = sprintf("%.2f",$server_load);
            $bwout_string = sprintf("%.3f",$server_bwout);
            $load_round = round($server_load);
     
            switch ($server_load) {
                case ($server_load > 99): $color = "black"; $heavy = true; break;
                case ($server_load >= 85): $color = "red"; $heavy = true; break;
                case ($server_load >= 75): $color = "yellow"; break;
                default: $color = "green";
                }
                $info = '<A HREF="http://'.$repeater.':$adminport/serverinfo.xml" target="_blank">'.$repeater.'</A>: <B>'. $load_string ."%</B> with ". $server_conns . " connections (". $bwout_string ." Mbps out)"; 
                print "<P>" . $info . "</P>"; 
     
            ?>
            <DIV style="height: 5px; background: none repeat scroll 0% 0% #ffffff; border: 2px solid;">
                <DIV style="height: 100%; background: <?=$color?>; color: black; width: <?=$load_round?>%;"></div>
            </div>
            <?php
 
        } // End Load Graph
         
    } // End Bandwidth Loop
 
 
    if($count) {
        $repeaterurl = "http://$repeater:$adminport/serverinfo";
        $repeaterxml = simplexml_load_file($repeaterurl);
         
        foreach ($repeaterxml->VHost as $vhost) {
            foreach ($vhost->Application as $app) {
                foreach ($app->ApplicationInstance as $instance) {
                    foreach ($instance->Client as $client) {
                        // Skip repeaters and encoders - FMS is already filtered out by the HTTPProvider
                        $skip = false; // Init variable
                        if (preg_match('(Repeater)',$client->Referrer)) { $skip = true; }
                        if (preg_match('(Kulabyte)',$client->Referrer)) { $skip = true; }
     
                        if (!$skip) { 
                            $protocol = (string)$client->Protocol;
                            $proto[$protocol]++;
                            $streamnames = (string)$client->StreamNames;
                            $streamarray=explode("/",$streamnames);
                            $str_array_len=sizeof($streamarray);
     
                            $str_end_index = $str_array_len -1;
                            $strname = $streamarray[$str_end_index];
 
                            if ($streamarray[0] == "amazons3") {
                                $vod[$strname]++;
                                } else {
                                $live[$strname]++;
                                }
                            $client_list[] = (string)$client->IpAddress;
 
                            if ($detailed) {
     
                                if ($client->UserAgent) // HTML clients will have a useragent defined
                                    { 
                                        echo $client->IpAddress.": ".$client->UserAgent ."<BR>\n"; 
                                        if (preg_match('(Roku)',$client->UserAgent)) { $agent['Roku']++; }
                                        if (preg_match('(iPhone)',$client->UserAgent)) { $agent['iPhone']++; }
                                        if (preg_match('(iPad)',$client->UserAgent)) { $agent['iPad']++; }
                                        if (preg_match('(iPod)',$client->UserAgent)) { $agent['iPod']++; }
                                        if (preg_match('(stagefright)',$client->UserAgent)) { $agent['Android']++; }
                                             
                                    } 
             
                                if ($client->FlashVersion) // Flash clients will have a different string. 
                                    { 
                                        echo $client->IpAddress.": Flash version " . $client->FlashVersion ."<BR>\n"; 
                                        $agent['Flash']++;
                                        } 
                                }   //Client Details
                            } // Detailed Loop
                    } //Client Loop
                } //Instance Loop 
            } // App Loop
        } //VHost Loop
 
    } // Count Loop
 
 
if ($geo_count) {
 
    print "<H3>Client Connections</H3>\n";
    echo "<SMALL>Last Refresh: ";
    echo $date = date("Y-m-d H:i:s");
    echo "</SMALL>\n";
 
    foreach($client_list as $client_ip) {
        $geo_record = geoip_record_by_name($client_ip);
        $cn = $geo_record['country_name'];
        $cc = strtolower($geo_record['country_code']);
        if ($cc == "us") {
            $sn = $geo_record['region'];
            $state_total[$sn]++;
            }
 
        $country_name[$cc]=$cn;
        $country_total[$cc]++;
    } //End Client iteration    
    arsort($country_total);
    print '<DIV class="countries">'."\n";
    print '<H4>Countries</H4>'."\n";
    foreach (array_keys($country_total) as $ccode) {
        print '<IMG SRC="images/flags/'.$ccode.'.png">&nbsp;';
        print "$country_name[$ccode] : ".$country_total[$ccode]."<BR>\n";
    } // End Country iteration
    print '</DIV>';
     
    arsort($state_total);
    print '<DIV class="states">'."\n";
    print '<H4>States</H4>'."\n";
    foreach (array_keys($state_total) as $state) {
        print "$state : ".$state_total[$state]."<BR>\n";
    } // End State iteration
    print '</DIV>';
 
} // End Geo Loop
 
if ($detailed) {
    $agent['iOS'] = $agent['iPhone'] + $agent ['iPad'] + $agent['iPod'];
    echo "<BR>\n";
    echo "Total HLS Clients: ". $proto['HLS']."<BR>\n";
    echo "Total Roku clients: ". $agent['Roku']."<BR>\n";
    echo "Total iOS clients: ". $agent['iOS'] ." (".$agent['iPhone']." iPhone,". $agent['iPad']." iPad,". $agent['iPod']." iPod)<BR>\n";
    echo "Total RTMP clients: ". $proto['RTMP']." (". $agent['Flash'] ." Flash)<BR>\n";
} // Detailed Loop
 
if ($simple_load) { printf("%.3f", $server_bwout); }
 
if ($simple_count) { print $server_conns; }
 
if ($stream_count) {
    print "<h3>Stream Connections</h3>\n";
    arsort($live);
    arsort($vod);
    print "<DIV CLASS=\"live\"><h4>Live</h4>\n";
        foreach (array_keys($live) as $lstr) { print strtoupper($lstr)." : ".$live[$lstr]."<BR>\n"; }
    print "</DIV><DIV CLASS=\"vod\"><h4>On-Demand</h4>\n";
        foreach (array_keys($vod) as $vstr) { print strtoupper($vstr)." : ".$vod[$vstr]."<BR>\n"; }
    print "</DIV>";
}
 
 
 
     
}
 
 
?>