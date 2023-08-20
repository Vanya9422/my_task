<?php
$urls = [];

foreach (range(0,10) as $int) {

	$number = random_int(1,10000000);

	$urls[] = "http://example.com/page-$number";
}

return $urls;
?>