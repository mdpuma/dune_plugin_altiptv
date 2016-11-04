<?php

$q_date = date('d-m-Y');

$doc = shell_exec("curl https://point.md/ru/tv/jurnaltv/$q_date 2>/dev/null");

$doc = preg_replace("/\n/", "", $doc);
$doc = preg_replace("/<li class=\"(m-old)?\"><i>([0-9:]+)<\/i>([^<]+)<\/li>/", "[$2|$3]\n", $doc);

$doc = strip_tags($doc);
//preg_match_all("/\[([0-9:]+)\|([^\]]+)\]/", $doc, $matches);

var_dump($doc);