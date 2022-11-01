<?php
	/* CONNECT TO MySQL 
	$link = mysql_connect('redacted', 'redacted', 'redacted');
	mysql_select_db('redacted');
	*/

	if (($_POST['formName']) || ($_GET['act'] == 'parseExample')) { require('statscalc.php'); exit(1); }

	$classRarr = Array('Carbine' => 'C','Melee' => 'M','Rifle' => 'R','Pistol' => 'P','Support' => 'S','Cannon' => 'CA');
	$typeRarr = Array('Laser' => 'L','BLP' => 'B','Shortblades' => 'SB','Longblades' => 'LB','Whip' => 'W','Power Fist' => 'PF','Plasma' => 'P','Rocket' => 'R','Grenade' => 'G','Clubs' => 'C','Gaus' => 'GA','Axes' => 'A');
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1.0-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>EuStats</title>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />

<style type="text/css" media="screen">@import url("style.css");</style>
<script src="stats.php"></script>
<script type="text/javascript">
	/************************************* BASE ************************************/
	var alreadyrunflag=0;
	if (document.addEventListener) { document.addEventListener("DOMContentLoaded", function(){alreadyrunflag=1; jsInit()}, false); } 
	else if (document.all && !window.opera) {
	  document.write('<script type="text/javascript" id="contentloadtag" defer="defer" src="javascript:void(0)"><\/script>');
	  var contentloadtag=document.getElementById("contentloadtag");
	  contentloadtag.onreadystatechange=function(){ if (this.readyState=="complete"){ alreadyrunflag=1;jsInit(); } }
	}
	window.onload=function(){ setTimeout("if (!alreadyrunflag) jsInit()", 0) }

	function jsInit () {
		filterWeaponClass('');
		filterAmpClass('All');
	}

	Object.prototype.size = function () {
		var len = this.length ? --this.length : -1;
		for (var k in this) { len++; }
		return len;
	}

	function G(el) { return document.getElementById(el); }
	/************************************* /BASE ***********************************/

	var currW = 0;
	var currA = 0;
	
	function filterWeaponClass(filter) {
		var wResults = 0;
		var wChType = 1;

		var out = '';
		out += '<select name="weapon" ID="select_weapons" style="width: 100%;" size="10" onChange="javascript:select_weapon(this.options[this.selectedIndex].value);">';
		var cl = G('select_class').options[G('select_class').selectedIndex].value;
		var ty = G('select_type').options[G('select_type').selectedIndex].value;
		for(var x=1;x<=w.size();x++) {
			if ((cl == 'All' || cl == w[x]['c']) && (ty == 'All' || ty == w[x]['t'])) {
				var selected = '';
				if (currW == x) { var selected = ' selected="1"'; wChType = 0; }
				if ((filter == '') || (w[x]['n'].search(new RegExp(filter, "i")) > 0)) { out += '<option value="'+x+'"'+selected+'>'+w[x]['n']+'</option>'; }
				wResults++;
			}
		}
		out += '</select>';
		G('select_weapons_holder').innerHTML = out;
		if ((wResults == 0) || (wChType == 1)) { currW = 0; calcValues(); }
	}
	
	function filterAmpClass(cl) {
		var aResults = 0;
		var aChType = 1;

		var out = '';
		out += '<select name="amp" ID="select_amps" style="width: 100%;" size="10" onChange="javascript:select_amp(this.options[this.selectedIndex].value);">';
		out += '<option value=""></option>';
		for(var x=1;x<=a.size();x++) {
			if (cl == 'P') { cl = 'L'; }
			if (cl == 'All' || cl == a[x]['t']) { 
				var selected = '';
				if (currA == x) { var selected = ' selected="1"'; aChType = 0; }
				out += '<option value="'+x+'"'+selected+' '+(currW != 0 && (a[x]['da'] > (w[currW]['da'] / 2))?'class="ampWarning"':'')+'>'+a[x]['n']+'</option>'; 
				aResults++;
			}
		}
		out += '</select>';
		G('select_amps_holder').innerHTML = out;
		if ((aResults == 0) || (aChType == 1)) { currA = 0; }
	}

	function select_weapon(ID) {
		clearData('w');
		currW = ID;
		var name = w[ID]['n'];
		G('w_maxTT').value = w[ID]['maTT'];
		G('w_minTT').value = w[ID]['miTT'];
		G('w_ammo').innerHTML = w[ID]['am'];
		G('w_decay').innerHTML = w[ID]['d'];
		G('w_markup').disabled = (name.indexOf('(') > 0)?0:1;
		filterAmpClass(w[ID]['t']);
		calcValues();
	}
	
	function select_amp(ID) {
		clearData('a');
		currA = ID;
		var name = a[ID]['n'];
		G('a_maxTT').value = a[ID]['maTT'];
		G('a_minTT').value = a[ID]['miTT'];
		G('a_ammo').innerHTML = a[ID]['am'];
		G('a_decay').innerHTML = a[ID]['d'];
		G('a_markup').disabled = (name.indexOf('(') > 0)?0:1;
		calcValues();
	}
	
	function clearData(aw) {
		var el = new Array(aw+'_markup',aw+'_marketvalue',aw+'_maxTT',aw+'_startTT',aw+'_endTT',aw+'_minTT',aw+'_TTleft',aw+'_TTleftMU',aw+'_shotsleft',aw+'_ammoleft');
		for (var x=0;x<el.length;x++) { G(el[x]).value = ''; }

		var el = new Array(aw+'_ammo',aw+'_decay','t'+aw+'_ammo','t'+aw+'_decay','t'+aw+'_decayMU','t'+aw+'_cost','t'+aw+'_costMU','tt_ammo','tt_decay','tt_decayMU','tt_cost','tt_costMU','tt_ttleftGun','tt_ttleftAmp','dmg_cost','dmg_dps','dmg_pec');
		for (var x=0;x<el.length;x++) { G(el[x]).innerHTML = '&nbsp;'; }
	}

	function calcValues() {
		if (currW != 0) {
			var w_minTT = (G('w_endTT').value == '')?G('w_minTT').value:G('w_endTT').value;
			var w_maxTT = (G('w_startTT').value == '')?G('w_maxTT').value:G('w_startTT').value;
			var w_markup = (G('w_markup').value == '')?100:G('w_markup').value;
			var w_TTleft = w_maxTT - w_minTT;
			var w_shotsleft = Math.ceil((w_TTleft*100) / w[currW]['d']);
			var w_ammoleft = w_shotsleft * w[currW]['am'];

			G('w_marketvalue').value = (w_maxTT / 100 * w_markup).toFixed(2);
			G('w_TTleft').value = w_TTleft.toFixed(2);
			G('w_TTleftMU').value = (w_TTleft / 100 * w_markup).toFixed(2);
			G('w_shotsleft').value = w_shotsleft;
			G('w_ammoleft').value = w_ammoleft;

			G('tw_ammo').innerHTML = w_ammoleft;
			G('tw_decay').innerHTML = w_TTleft.toFixed(2) + " PED";
			G('tw_decayMU').innerHTML = (w_TTleft / 100 * w_markup).toFixed(2) + " PED";
			G('tw_cost').innerHTML = ((w_ammoleft / 100) + w_TTleft).toFixed(2) + " PED";
			G('tw_costMU').innerHTML = ((w_ammoleft / 100) + (w_TTleft / 100 * w_markup)).toFixed(2) + " PED";
		} else { clearData('w'); }

		if (currA != 0) {
			var a_minTT = (G('a_endTT').value == '')?G('a_minTT').value:G('a_endTT').value;
			var a_maxTT = (G('a_startTT').value == '')?G('a_maxTT').value:G('a_startTT').value;
			var a_markup = (G('a_markup').value == '')?100:G('a_markup').value;
			var a_TTleft = a_maxTT - a_minTT;
			var a_shotsleft = Math.ceil((a_TTleft*100) / a[currA]['d']);
			var a_ammoleft = a_shotsleft * a[currA]['am'];

			G('a_marketvalue').value = (a_maxTT / 100 * a_markup).toFixed(2);
			G('a_TTleft').value = a_TTleft.toFixed(2);
			G('a_TTleftMU').value = (a_TTleft / 100 * a_markup).toFixed(2);
			G('a_shotsleft').value = a_shotsleft;
			G('a_ammoleft').value = a_ammoleft;

			G('ta_ammo').innerHTML = a_ammoleft;
			G('ta_decay').innerHTML = a_TTleft.toFixed(2) + " PED";
			G('ta_decayMU').innerHTML = (a_TTleft / 100 * a_markup).toFixed(2) + " PED";
			G('ta_cost').innerHTML = ((a_ammoleft / 100) + a_TTleft).toFixed(2) + " PED";
			G('ta_costMU').innerHTML = ((a_ammoleft / 100) + (a_TTleft / 100 * a_markup)).toFixed(2) + " PED";
		} else { clearData('a'); }

		if ((currW != 0) && (currA != 0)) {
			var tt_shotsleft = (w_shotsleft < a_shotsleft)?w_shotsleft:a_shotsleft;
			var tt_cost = ((tt_shotsleft * (w[currW]['d'] + a[currA]['d'])) / 100);
			var tt_w_decayMU = (tt_shotsleft * w[currW]['d'] * w_markup) / 100 / 100;
			var tt_a_decayMU = (tt_shotsleft * a[currA]['d'] * a_markup) / 100 / 100;
			var tt_ammo = Math.ceil(tt_shotsleft * (w[currW]['am'] + a[currA]['am']));

			var tt_gun_cost = ((tt_shotsleft * w[currW]['d'])/100) + (tt_shotsleft * w[currW]['am'] / 100);
			var tt_amp_cost = ((tt_shotsleft * a[currA]['d'])/100) + (tt_shotsleft * a[currA]['am'] / 100);
			var tt_gun_costMU = ((tt_shotsleft * w[currW]['d'])* w_markup/100/100) + (tt_shotsleft * w[currW]['am'] / 100);
			var tt_amp_costMU = ((tt_shotsleft * a[currA]['d'])* a_markup/100/100) + (tt_shotsleft * a[currA]['am'] / 100);

			var w_decay = (w_maxTT - ((tt_shotsleft * w[currW]['d']) / 100)).toFixed(2);
			var a_decay = (a_maxTT - ((tt_shotsleft * a[currA]['d']) / 100)).toFixed(2);
			var w_left = (w_decay - w_minTT);
			var a_left = (a_decay - a_minTT);
			if (w_left < 0) { w_left = 0; }
			if (a_left < 0) { a_left = 0; }

			G('tt_ammo').innerHTML = tt_ammo + " (" + (tt_shotsleft * w[currW]['am']) + " + " + (tt_shotsleft * a[currA]['am']) + ")";
			G('tt_decay').innerHTML = ((tt_shotsleft * (w[currW]['d'] + a[currA]['d'])) / 100).toFixed(2) + " (" + (tt_shotsleft * w[currW]['d'] / 100).toFixed(2) + " + " + (tt_shotsleft * a[currA]['d'] / 100).toFixed(2) + ") PED";
			G('tt_decayMU').innerHTML = (tt_w_decayMU + tt_a_decayMU).toFixed(2) + " (" + (tt_w_decayMU).toFixed(2) + " + " + (tt_a_decayMU).toFixed(2) + ") PED";
			G('tt_cost').innerHTML = (tt_cost + (tt_ammo / 100)).toFixed(2) + " (" + (tt_gun_cost).toFixed(2) + " + " + (tt_amp_cost).toFixed(2) + ") PED";
			G('tt_costMU').innerHTML = ((tt_ammo / 100) + tt_w_decayMU + tt_a_decayMU).toFixed(2) + " (" + (tt_gun_costMU).toFixed(2) + " + " + (tt_amp_costMU).toFixed(2) + ") PED";
			G('tt_ttleftGun').innerHTML = w_decay + ' PED ('+(w_left).toFixed(2)+' PED usable)';
			G('tt_ttleftAmp').innerHTML = a_decay + ' PED ('+(a_left).toFixed(2)+' PED usable)';
		}
		
		var dmg_amp_dmg = 0, dmg_cost = 0, dmg_costMU = 0, dmg_dps = 0, dmg_total_dmg = 0, dmg_dps_avg = 0, dmg_pec = 0, dmg_pecMU = 0;

		if (currA != 0) { 
			dmg_cost += a[currA]['am'] + a[currA]['d']; 
			dmg_costMU += a[currA]['am'] + (a[currA]['d'] / 100 * a_markup); 
			dmg_amp_dmg = a[currA]['da'] > (w[currW]['da'] / 2)?(w[currW]['da'] / 2):a[currA]['da'];
		}
		if (currW != 0) {
			dmg_cost += w[currW]['am'] + w[currW]['d'];  
			dmg_costMU += w[currW]['am'] + (w[currW]['d'] / 100 * w_markup);

			dmg_total_dmg = (dmg_amp_dmg + w[currW]['da']);
			dmg_dps = dmg_total_dmg / 60 * w[currW]['a'];
			dmg_dps_avg = (dmg_total_dmg * 0.75) / 60 * w[currW]['a'];
			dmg_pec = (dmg_total_dmg * 0.75) / dmg_cost;
			dmg_pecMU = (dmg_total_dmg * 0.75) / dmg_costMU;
		}

		G('dmg_cost').innerHTML = dmg_cost.toFixed(2)+' (w/%: '+dmg_costMU.toFixed(2)+')';
		G('dmg_dps').innerHTML = dmg_dps_avg.toFixed(2)+' (max DPS: '+dmg_dps.toFixed(2)+')';
		G('dmg_pec').innerHTML = dmg_pec.toFixed(2)+' (w/%: '+dmg_pecMU.toFixed(2)+')';
	}
</script>

</head>
<body>

<form method="post" action="?" enctype="multipart/form-data">
<div class="floaterB">
	<div class="floaterL">
		<select ID="select_class" onChange="javascript:filterWeaponClass('');">
			<option value="All">All</option>
			<?php foreach ($classRarr as $key => $item) { echo '<option value="'.$item.'">'.$key.'</option>'; } ?>
		</select>
		<select ID="select_type" onChange="javascript:filterWeaponClass('');">
			<option value="All">All</option>
			<?php foreach ($typeRarr as $key => $item) { echo '<option value="'.$item.'">'.$key.'</option>'; } ?>
		</select>
	</div>
	<div style="float: right;">
		filter weapon <input type="text" onKeyUp="javascript:filterWeaponClass(this.value)" />
	</div>
	<br class="clear" />
</div>

<div ID="select_weapons_holder" class="floaterL"></div>
<div ID="select_amps_holder" class="floaterR"></div>
<br class="clear" />

<div class="spacer"></div>
	
<div class="floaterL">

	<div class="floaterTT">max.TT</div>
	<div class="floaterTT">start TT</div>
	<div class="floaterTT">end TT</div>
	<div class="floaterTT">min.TT</div>
	<br class="clear" />
	<input type="text" class="floaterTT" ID="w_maxTT" name="w_maxTT" />
	<input type="text" class="floaterTT" ID="w_startTT" name="w_startTT" onKeyUp="javascript:calcValues();" />
	<input type="text" class="floaterTT" ID="w_endTT" name="w_endTT" onKeyUp="javascript:calcValues();" />
	<input type="text" class="floaterTT" ID="w_minTT" name="w_minTT" />
	<br class="clear" />

	<br />
	
	<div class="floaterT2">ammo</div> <div class="floaterT" ID="w_ammo"></div>
	<br class="clear" />
	<div class="floaterT2">decay</div><div class="floaterT" ID="w_decay"></div>PEC
	<br class="clear" />	

	<br />

	<div class="floaterT2">markup</div> <input type="text" class="floaterT" disabled="1" ID="w_markup" name="w_markup" onKeyUp="javascript:calcValues();" />%
	<br class="clear" />
	<div class="floaterT2">market value</div> <input type="text" class="floaterT" ID="w_marketvalue" />PED
	<br class="clear" />
	<div class="floaterT2">usable TT left</div> <input type="text" class="floaterT" ID="w_TTleft" />PED
	<br class="clear" />
	<div class="floaterT2">usable TT left (w/%)</div> <input type="text" class="floaterT" ID="w_TTleftMU" />PED
	<br class="clear" />
	<div class="floaterT2">uses left</div> <input type="text" class="floaterT" ID="w_shotsleft" />
	<br class="clear" />
	<div class="floaterT2">ammo left</div> <input type="text" class="floaterT" ID="w_ammoleft" />
	<br class="clear" />
	
</div>
<div class="floaterR">
	<div class="floaterTT">max.TT</div>
	<div class="floaterTT">start TT</div>
	<div class="floaterTT">end TT</div>
	<div class="floaterTT">min.TT</div>
	<br class="clear" />
	<input type="text" class="floaterTT" ID="a_maxTT" name="a_maxTT" />
	<input type="text" class="floaterTT" ID="a_startTT" name="a_startTT" onKeyUp="javascript:calcValues();" />
	<input type="text" class="floaterTT" ID="a_endTT" name="a_endTT" onKeyUp="javascript:calcValues();" />
	<input type="text" class="floaterTT" ID="a_minTT" name="a_minTT" />
	<br class="clear" />
	
	<br />

	<div class="floaterT2">ammo</div> <div class="floaterT" ID="a_ammo"></div>
	<br class="clear" />
	<div class="floaterT2">decay</div> <div class="floaterT" ID="a_decay" /></div>PEC
	<br class="clear" />	

	<br />

	<div class="floaterT2">markup</div>	<input type="text" class="floaterT" disabled="1" ID="a_markup" name="a_markup" onKeyUp="javascript:calcValues();" />%
	<br class="clear" />
	<div class="floaterT2">market value</div> <input type="text" class="floaterT" ID="a_marketvalue" />PED
	<br class="clear" />
	<div class="floaterT2">usable TT left</div> <input type="text" class="floaterT" ID="a_TTleft" />PED
	<br class="clear" />
	<div class="floaterT2">usable TT left (w/%)</div> <input type="text" class="floaterT" ID="a_TTleftMU" />PED
	<br class="clear" />
	<div class="floaterT2">uses left</div> <input type="text" class="floaterT" ID="a_shotsleft" />
	<br class="clear" />
	<div class="floaterT2">ammo left</div> <input type="text" class="floaterT" ID="a_ammoleft" />
	<br class="clear" />
</div>

<br class="clear" /><br />
<div class="spacer"></div>

<div class="floaterB">
	<br />
	<div class="floaterL">before weapon breaks<br />
		<div class="floaterT3">ammo</div>		<div class="floaterT4" ID="tw_ammo">&nbsp;</div><br class="clear" />
		<div class="floaterT3">decay</div>		<div class="floaterT4" ID="tw_decay">&nbsp;</div><br class="clear" />
		<div class="floaterT3">decay w/%</div>	<div class="floaterT4" ID="tw_decayMU">&nbsp;</div><br class="clear" />
		<div class="floaterT3">cost</div>		<div class="floaterT4" ID="tw_cost">&nbsp;</div><br class="clear" />
		<div class="floaterT3">cost w/%</div>	<div class="floaterT4" ID="tw_costMU">&nbsp;</div><br class="clear" />
	</div> 
	<div class="floaterR">before amp breaks<br /> 
		<div class="floaterT3">ammo</div>		<div class="floaterT4" ID="ta_ammo">&nbsp;</div><br class="clear" />
		<div class="floaterT3">decay</div>		<div class="floaterT4" ID="ta_decay">&nbsp;</div><br class="clear" />
		<div class="floaterT3">decay w/%</div>	<div class="floaterT4" ID="ta_decayMU">&nbsp;</div><br class="clear" />
		<div class="floaterT3">cost</div>		<div class="floaterT4" ID="ta_cost">&nbsp;</div><br class="clear" />
		<div class="floaterT3">cost w/%</div>	<div class="floaterT4" ID="ta_costMU">&nbsp;</div><br class="clear" />
		</div>	
	<br class="clear" /><br />
	<div>gun + amp (max 1 amp)</div> 
		<div class="floaterT3">ammo</div>		<div class="floaterT5" ID="tt_ammo">&nbsp;</div><br class="clear" />
		<div class="floaterT3">decay</div>		<div class="floaterT5" ID="tt_decay">&nbsp;</div><br class="clear" />
		<div class="floaterT3">decay w/%</div>	<div class="floaterT5" ID="tt_decayMU">&nbsp;</div><br class="clear" />
		<div class="floaterT3">cost</div>		<div class="floaterT5" ID="tt_cost">&nbsp;</div><br class="clear" />
		<div class="floaterT3">cost w/%</div>	<div class="floaterT5" ID="tt_costMU">&nbsp;</div><br class="clear" />
		<div class="floaterT3">gun TT left </div>	<div class="floaterT5" ID="tt_ttleftGun">&nbsp;</div><br class="clear" />
		<div class="floaterT3">amp TT left </div>	<div class="floaterT5" ID="tt_ttleftAmp">&nbsp;</div><br class="clear" />
	<br />
	<div>damage</div>
		<div class="floaterT3">cost/use</div>		<div class="floaterT5" ID="dmg_cost">&nbsp;</div><br class="clear" />
		<div class="floaterT3">DPS</div>			<div class="floaterT5" ID="dmg_dps">&nbsp;</div><br class="clear" />
		<div class="floaterT3">dmg/pec</div>		<div class="floaterT5" ID="dmg_pec">&nbsp;</div><br class="clear" />
	<br />
</div>

<div class="spacer"></div>
<br />
Upload Entropia log file: &nbsp;  <input type="file" name="log" /> &nbsp; <input type="submit" value="Send" style="width: 60px;" /> &nbsp;&nbsp; (<a href="?act=parseExample">example of parsed file</a>)
<input type="hidden" name="formName" value="weapons" />
</form>

</body>
</html>

<?php
	/*
	$f = file('weps.txt');
	foreach($f as $l) {
		$w = explode(chr(9),trim($l));
		$t = explode(" ",$w[12]);
		$w[12] = str_replace("%","",$t[0]);
		$w[14] = $w[14]=='Yes'?1:0;
		$sql = "INSERT INTO euweps (name, class, type, damage, range, attacks, dps, decay, ammo, cost, maxTT, minTT, markup, dpp, SIB, source) VALUES ('".mysql_real_escape_string($w[0])."','".$w[1]."','".$w[2]."','".$w[3]."','".$w[4]."','".$w[5]."','".$w[6]."','".$w[7]."','".$w[8]."','".$w[9]."','".$w[10]."','".$w[11]."','".$w[12]."','".$w[13]."','".$w[14]."','".$w[15]."')";
		// echo $sql."<br />";
		// @mysql_query($sql) or die(mysql_error());
	}
	*/

	/*
	$f = file('amps.txt');
	foreach($f as $l) {
		$w = explode(chr(9),trim($l));
		$sql = "INSERT INTO euamps (name, damage, decay, ammo, cost, minTT, maxTT, dpp) VALUES ('".mysql_real_escape_string($w[0])."','".$w[2]."','".$w[5]."','".$w[6]."','".$w[7]."','".$w[9]."','".$w[8]."','".$w[11]."')";
		// echo $sql."<br />";
		// @mysql_query($sql) or die(mysql_error());
	}
	*/
?>