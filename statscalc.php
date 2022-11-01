<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1.0-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>EuStats</title>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />

<style type="text/css" media="screen">@import url("style.css");</style>
</head>

<body>

<?php
	function duration($timestamp){ 
		$years=floor($timestamp/(60*60*24*365));$timestamp%=60*60*24*365; 
		$weeks=floor($timestamp/(60*60*24*7));$timestamp%=60*60*24*7; 
		$days=floor($timestamp/(60*60*24));$timestamp%=60*60*24; 
		$hrs=floor($timestamp/(60*60));$timestamp%=60*60; 
		$mins=floor($timestamp/60);$secs=$timestamp%60; 
		
		if ($years >= 1) { $str.= $years.' years '; } 
		if ($weeks >= 1) { $str.= $weeks.' weeks '; } 
		if ($days >= 1) { $str.=$days.' days '; } 
		if ($hrs >= 1) { $str.=$hrs.' hours '; } 
		if ($mins >= 1) { $str.=$mins.' minutes '; } 
		// if ($secs >= 1) { $str.=$sec.' s '; } 

		 return $str;   
	}

	function errmsg ($msg) {
		return '<div class="error">'.$msg.'</div>';
	}
	

	if ($_GET['act'] == 'parseExample') {
		$_POST['weapon'] = 400;
		$_POST['amp'] = 28;
		$_POST['w_startTT'] = 92.18;
		$_POST['w_endTT'] = 75.50;
	} else {
		$errors = Array();
		if (!$_POST['weapon']) { $errors[] = "Missing weapon."; }
		if (!$_FILES['log']['name']) { $errors[] = "Missing log file."; }
		if (count($errors) > 0) { echo errmsg(implode("<br />",$errors)); exit(1); }
	}

	$result = mysql_query("SELECT * FROM euweps WHERE ID=".$_POST['weapon']);
	$weapon = mysql_fetch_assoc($result);

	if ($_POST['amp']) {
		$result = mysql_query("SELECT * FROM euamps WHERE ID=".$_POST['amp']);
		$amp = mysql_fetch_assoc($result);
	}

	/////////// FORMAT
	$startTT = $_POST['w_startTT']?$_POST['w_startTT']:$_POST['w_maxTT'];
	$endTT = $_POST['w_endTT']?$_POST['w_endTT']:$_POST['w_minTT'];
	$weaponMarkup = $_POST['w_markup']?$_POST['w_markup']:100;
	$ampMarkup = $_POST['a_markup']?$_POST['a_markup']:100;

	// OWN
	$hits = 0;
	$crits = 0;
	$totalDmg = 0;
	$totalNonCritDmg = 0;
	$lowestDmg = 10000;
	$highestDmg = 0;
	$lowestCrit = 10000;
	$highestCrit = 0;

	// MOB
	$hitsTaken = 0;
	$critsTaken = 0;
	$dmgTaken = 0;
	$lowestMobDmg = 10000;
	$highestMobDmg = 0;
	$lowestMobCrit = 10000;
	$highestMobCrit = 0;

	// OTHER
	$faps = 0;
	$healed = 0;

	$tt_spent = $startTT - $endTT;
	$shots = ceil(($tt_spent * 100) / ($weapon['decay']));
	$weaponCost = (($weapon['decay'] * $weaponMarkup) / 100) + $weapon['ammo'];
	$ampCost = (($amp['decay'] * $ampMarkup) / 100) + $amp['ammo'];
	$totalCost = $weaponCost + $ampCost;
	
	$file = ($_GET['act'] == 'parseExample')?'example.log':$_FILES['log']['tmp_name'];
	$f = file($file);
	foreach ($f as $l) {
		$line = explode(" ",$l);
		
		$isDmg = FALSE;
		$isCrit = FALSE;
		$isMobDmg = FALSE;
		$isMobCrit = FALSE;

		if ($line[3].$line[4] == 'Youinflicted') { $isDmg = TRUE; }
		if ($line[3].$line[4] == 'Criticalhit') { 
			if ($line[8].$line[9] == 'Youinflict') { $isCrit = TRUE; }
			if ($line[8].$line[9] == 'Youtake') { $isMobCrit = TRUE;  }
		}
		if ($line[3].$line[4] == 'Youtake') { $isMobDmg = TRUE; }
		if ($line[3].$line[4].$line[5] == 'Youarehealed') { $faps++; $healed += $line[6]; }

		////////////////////////////////////////////////////////////// OWN STATS
		if ($isCrit || $isDmg) { 
			if (!$firstShotTime) { $firstShotTime = $line[0]." ".$line[1]; }
			$lastShotTime = $line[0]." ".$line[1];		
			$hits++; 	
		}
		if ($isDmg)  { 
			$dmg = $line[5];
			if ($dmg < $lowestDmg) { $lowestDmg = $dmg; }
			if ($dmg > $highestDmg) { $highestDmg = $dmg; }
			$totalDmg += $dmg;
			$totalNonCritDmg += $dmg;
		}
		if ($isCrit) { 
			$dmg = $line[10];
			if ($dmg < $lowestCrit) { $lowestCrit = $dmg; }
			if ($dmg > $highestCrit) { $highestCrit = $dmg; }
			$totalDmg += $dmg; 
			$crits++; 
		}

		////////////////////////////////////////////////////////////// MOB STATS
		if ($isMobDmg || $isMobCrit) { $hitsTaken++; }
		if ($isMobDmg) {
			$dmg = $line[5];
			if ($dmg < $lowestMobDmg) { $lowestMobDmg = $dmg; }
			if ($dmg > $highestMobDmg) { $highestMobDmg = $dmg; }
			$dmgTaken += $dmg; 
			$dmgTakenNonCrits += $dmg;
		}
		if ($isMobCrit) {
			$dmg = $line[10];
			if ($dmg < $lowestMobCrit) { $lowestMobCrit = $dmg; }
			if ($dmg > $highestMobCrit) { $highestMobCrit = $dmg; }
			$dmgTaken += $dmg; 
			$critsTaken++;
		}

	}

	$missPR = 100 - (($hits / $shots) * 100);
	$critPR = (($crits / $hits) * 100);
	$avgNonCritDmg = ($totalNonCritDmg / ($hits - $crits));
	$avgDmg = ($totalDmg / $hits);

	$avgMobDmg = ($dmgTaken / $hitsTaken);
	$avgMobNonCritDmg = ($dmgTakenNonCrits / ($hitsTaken - $critsTaken));

	$totalCostRun = ($totalCost * $shots);
	$dmgPEC = $totalDmg / $totalCostRun;

	$w_name = $weapon['name'];
	$a_name = $amp[ID]?' + '.$amp['name']:'';

	$huntDuration = strtotime($lastShotTime) - strtotime($firstShotTime);


?>

<h1><?php echo $w_name.$a_name; ?></h1> 
<div>Statistics</div> 
	<div class="floaterT6">Shots</div>		<div class="floaterT5"><?php echo $shots; ?></div><br class="clear" />
	<div class="floaterT6">Hits</div>		<div class="floaterT5"><?php echo $hits; ?></div><br class="clear" />
	<div class="floaterT6">Crits</div>		<div class="floaterT5"><?php echo $crits." (".round($critPR,2)."%)"; ?></div><br class="clear" />
	<div class="floaterT6">Misses</div>		<div class="floaterT5"><?php echo ($shots - $hits)." (".round($missPR,2)."%)"; ?></div><br class="clear" />
	<div class="floaterT6">Total Dmg</div>	<div class="floaterT5"><?php echo $totalDmg; ?></div><br class="clear" />
	<div class="floaterT6">Total cost</div>	<div class="floaterT5"><?php echo round($totalCostRun / 100,2); ?> PED</div><br class="clear" />
	<div class="floaterT6">You healed</div>		<div class="floaterT5"><?php echo $healed." (".$faps." uses)"; ?></div><br class="clear" />
	<br />
<div>Damages</div> 
	<div class="floaterT6">Avg. Dmg</div>		<div class="floaterT5"><?php echo round($avgNonCritDmg,2); ?></div><br class="clear" />
	<div class="floaterT6">Avg. Dmg w/ crits</div><div class="floaterT5"><?php echo round($avgDmg,2); ?></div><br class="clear" />
	<div class="floaterT6">Lowest hit</div>		<div class="floaterT5"><?php echo $lowestDmg; ?></div><br class="clear" />
	<div class="floaterT6">Highest hit</div>	<div class="floaterT5"><?php echo $highestDmg; ?></div><br class="clear" />
	<div class="floaterT6">Lowest Crit</div>	<div class="floaterT5"><?php echo $lowestCrit; ?></div><br class="clear" />
	<div class="floaterT6">Highest Crit</div>	<div class="floaterT5"><?php echo $highestCrit; ?></div><br class="clear" />
	<div class="floaterT6">Dmg/PEC</div>		<div class="floaterT5"><?php echo round($dmgPEC,3); ?></div><br class="clear" />
	<br />
<div>Times</div> 
	<div class="floaterT6">First shot</div>		<div class="floaterT5"><?php echo $firstShotTime; ?></div><br class="clear" />
	<div class="floaterT6">Last shot</div>		<div class="floaterT5"><?php echo $lastShotTime; ?></div><br class="clear" />
	<div class="floaterT6">Duration</div>		<div class="floaterT5"><?php echo duration($huntDuration); ?></div><br class="clear" /> 
	<div class="floaterT6">Hunt DPS</div>		<div class="floaterT5"><?php echo round($totalDmg / $huntDuration,2); ?></div><br class="clear" /> 
	<br />
<div>Mob statistics</div> 
	<div class="floaterT6">Dmg to you</div>		<div class="floaterT5"><?php echo $dmgTaken." (".$hitsTaken." hits)"; ?></div><br class="clear" />
	<div class="floaterT6">Avg. Dmg</div>		<div class="floaterT5"><?php echo round($avgMobNonCritDmg,2); ?></div><br class="clear" />
	<div class="floaterT6">Avg. Dmg w/ crits</div><div class="floaterT5"><?php echo round($avgMobDmg,2); ?></div><br class="clear" />
	<div class="floaterT6">Lowest hit</div>		<div class="floaterT5"><?php echo $lowestMobDmg; ?></div><br class="clear" />
	<div class="floaterT6">Highest hit</div>	<div class="floaterT5"><?php echo $highestMobDmg; ?></div><br class="clear" />
	<div class="floaterT6">Lowest Crit</div>	<div class="floaterT5"><?php echo $lowestMobCrit; ?></div><br class="clear" />
	<div class="floaterT6">Highest Crit</div>	<div class="floaterT5"><?php echo $highestMobCrit; ?></div><br class="clear" />
	<br />
<br />

</body>
</html>