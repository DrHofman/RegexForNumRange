<?php

require_once 'RegexForNumRange.inc.php';


echo $regex = RegexForNumRange::range(105, 111);

function makeNumbers() {
	$min = rand(0, 999);
	$max = rand($min, 999);

	return [$min, $max];
}

$i = 0;
while ($i < 10000) {
	list($min, $max) = makeNumbers();
	$regex = RegexForNumRange::range($min, $max);
	$pattern = sprintf('/%s/', $regex);
	$range = range($min, $max);

	foreach ($range as $key => $value) {
		if (preg_match($pattern, $value) != 1) {
			echo sprintf('Range: [%s-%s], error at %s ## %s <br />', $min, $max, $value, $pattern);
		}
	}

	$i++;
}
