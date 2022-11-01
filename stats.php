var w = new Object;
var a = new Object;
<?php
	$link = mysql_connect('db1.kapsi.fi', 'lost', 'xMpYKnxbhC');
	mysql_select_db('lost');
			
	$classRarr = Array('Carbine' => 'C','Melee' => 'M','Rifle' => 'R','Pistol' => 'P','Support' => 'S','Cannon' => 'CA');
	$typeRarr = Array('Laser' => 'L','BLP' => 'B','Shortblades' => 'SB','Longblades' => 'LB','Whip' => 'W','Power Fist' => 'PF','Plasma' => 'P','Rocket' => 'R','Grenade' => 'G','Clubs' => 'C','Gaus' => 'GA','Axes' => 'A');
	
	$result = mysql_query("SELECT ID,name,class,damage,type,attacks,decay,ammo,maxTT,minTT FROM euweps");
	while ($row = mysql_fetch_assoc($result)) {
		echo "w['".$row['ID']."'] = new Object;";
		echo "w['".$row['ID']."']['n'] = '".addslashes($row['name'])."';";
		echo "w['".$row['ID']."']['c'] = '".$classRarr[$row['class']]."';";
		echo "w['".$row['ID']."']['t'] = '".$typeRarr[$row['type']]."';";
		echo "w['".$row['ID']."']['da'] = ".$row['damage'].";";
		echo "w['".$row['ID']."']['a'] = ".$row['attacks'].";";
		echo "w['".$row['ID']."']['d'] = ".$row['decay'].";";
		echo "w['".$row['ID']."']['am'] = ".$row['ammo'].";";
		echo "w['".$row['ID']."']['maTT'] = ".$row['maxTT'].";";
		echo "w['".$row['ID']."']['miTT'] = ".$row['minTT'].";";
	}

	$result = mysql_query("SELECT ID,name,damage,decay,ammo,maxTT,minTT,type FROM euamps");
	while ($row = mysql_fetch_assoc($result)) {
		echo "a['".$row['ID']."'] = new Object;";
		echo "a['".$row['ID']."']['n'] = '".addslashes($row['name'])."';";
		echo "a['".$row['ID']."']['da'] = ".$row['damage'].";";
		echo "a['".$row['ID']."']['d'] = ".$row['decay'].";";
		echo "a['".$row['ID']."']['am'] = ".$row['ammo'].";";
		echo "a['".$row['ID']."']['maTT'] = ".$row['maxTT'].";";
		echo "a['".$row['ID']."']['miTT'] = ".$row['minTT'].";";
		echo "a['".$row['ID']."']['t'] = '".$typeRarr[$row['type']]."';";
	}
?>
