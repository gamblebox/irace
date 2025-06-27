<?php
$data = '8';
	$odds = 1;
	$bit = str_pad(decbin((int) $data), "11", "0", STR_PAD_LEFT);
	echo $bit[0] . PHP_EOL;
	exit();
	foreach (explode('', $bit) as $index => $value) {
		echo $value . PHP_EOL;
		if ($value == '1') {
			switch ($index) {
				case 0:
					$odds *= $config->odds_powerballodd;
					break;
				case 1:
					$odds *= $config->odds_powerballeven;
					break;
				case 2:
					$odds *= $config->odds_powerballunder;
					break;
				case 3:
					$odds *= $config->odds_powerballover;
					break;
				case 4:
					$odds *= $config->odds_ballsumodd;
					break;
				case 5:
					$odds *= $config->odds_ballsumeven;
					break;
				case 6:
					$odds *= $config->odds_ballsumunder;
					break;
				case 7:
					$odds *= $config->odds_ballsumover;
					break;
				case 8:
					$odds *= $config->odds_ballsumbig;
					break;
				case 9:
					$odds *= $config->odds_ballsummiddle;
					break;
				case 10:
					$odds *= $config->odds_ballsumsmall;
					break;
				default:
					break;
			}
		}
	}