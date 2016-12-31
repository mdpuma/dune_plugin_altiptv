<?php

$q_date = date('d-m-Y');

$doc = shell_exec("curl https://point.md/ru/tv/jurnaltv/$q_date 2>/dev/null");

$doc = preg_replace("/\n/", "", $doc);

$patterns = "/<li class=\"(m-old)?\"><i>([0-9:]+)<\/i>([^<]+)<\/li>/";
$replace = "[$2|$3]\n";
$doc = strip_tags(preg_replace($patterns, $replace, $doc));

// $doc = strip_tags($doc);

preg_match_all("/\[([0-9:]+)\|([^\]]+)\]/", $doc, $matches);

if(empty($matches[1])) die('No EPG data.');
$last_time = 0;
foreach($matches[1] as $key => $time) {
      $name = htmlspecialchars_decode($matches[2][$key]);
      $u_time = strtotime("$epg_date $time");
      $last_time = ($u_time < $last_time) ? $u_time + 86400  : $u_time;
      $epg[$last_time]["name"] = $name;
      $epg[$last_time]["desc"] = '';
}

var_dump($epg);