<?php
	/* MySQL -linkin redacted */

	$gun = 400;
	$amp = 28;
	$result = mysql_query("SELECT * FROM euweps WHERE ID=".$gun);
	$weapon = mysql_fetch_assoc($result);
	$result = mysql_query("SELECT * FROM euamps WHERE ID=".$amp);
	$amp = mysql_fetch_assoc($result);
	
	/////////// FORMAT
	$hits = 0;
	$crits = 0;
	$totalDmg = 0;
	$lowestDmg = 10000;
	$highestDmg = 0;
	$lowestCrit = 10000;
	$highestCrit = 0;

	$startTT = 92.18;
	$endTT = 75.50;
	$weaponMarkup = 100;
	$ampMarkup = 100;

	$tt_spent = $startTT - $endTT;
	$shots = ceil(($tt_spent * 100) / ($weapon['decay']));
	$weaponCost = (($weapon['decay'] * $weaponMarkup) / 100) + $weapon['ammo'];
	$ampCost = (($amp['decay'] * $ampMarkup) / 100) + $amp['ammo'];
	$totalCost = $weaponCost + $ampCost;
	
	echo "<br /><br />";

	$f = file('chat.log');
	foreach ($f as $l) {
		$line = explode(" ",$l);
		
		$isDmg = FALSE;
		$isCrit = FALSE;
		if ($line[3].$line[4] == 'Youinflicted') { $isDmg = TRUE; }
		if ($line[3].$line[4] == 'Criticalhit') { $isCrit = TRUE; }
		
		if ($isCrit || $isDmg) { $hits++; }
		if ($isDmg)  { 
			$dmg = $line[5];
			if ($dmg < $lowestDmg) { $lowestDmg = $dmg; }
			if ($dmg > $highestDmg) { $highestDmg = $dmg; }
			$totalDmg += $dmg;
		}
		if ($isCrit) { 
			$dmg = $line[10];
			if ($dmg < $lowestCrit) { $lowestCrit = $dmg; }
			if ($dmg > $highestCrit) { $highestCrit = $dmg; }
			$totalDmg += $dmg; 
			$crits++; 
		}

	}

	// ($shots * $totalCost)

	$missPR = 100 - (($hits / $shots) * 100);
	$avgDmg = ($totalDmg / $hits);
	$totalCostRun = ($totalCost * $shots);
	$dmgPEC = $totalDmg / $totalCostRun;

	echo "Shots: ".$shots."<br />";
	echo "Hits: ".$hits."<br />";
	echo "Crits: ".$crits."<br />";
	echo "Miss %: ".round($missPR,2)."<br />";
	echo "Total Dmg: ".$totalDmg."<br />";
	echo "Total cost: ".round($totalCostRun / 100,2)."<br />";
	echo "<br />";
	echo "Avg. Dmg: ".round($avgDmg,2)."<br />";
	echo "Lowest hit: ".$lowestDmg."<br />";
	echo "Highest hit: ".$highestDmg."<br />";
	echo "Lowest crit: ".$lowestCrit."<br />";
	echo "Highest crit: ".$highestCrit."<br />";
	echo "<br />";
	echo "Dmg/PEC: ".round($dmgPEC,2)."<br />";
?>