<?php
/* Copyright (C) 2018	Andreu Bisquerra	<jove@bisquerra.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

define('NOCSRFCHECK',1);	// This is main home and login page. We must be able to go on it from another web site.

$res=@include("../main.inc.php");
if (! $res) $res=@include("../../main.inc.php"); 
$floor=GETPOST('floor');
if ($floor=="") $floor=1;
$id = GETPOST('id');
$action = GETPOST('action');
$left = GETPOST('left');
$top = GETPOST('top');
$place = GETPOST('place');
$after = GETPOST('after');
$mode = GETPOST('mode');

if ($action=="getTables"){
    $sql="SELECT * from ".MAIN_DB_PREFIX."takepos_floor_tables where floor=$floor";
    $resql = $db->query($sql);
    $rows = array();
    while($row = $db->fetch_array ($resql)){
        if ($row['label']=="") $row['label']=$row['rowid'];
        $rows[] = $row;
    }  
    echo json_encode($rows);
    exit;
}

if ($action=="update")
{
    if ($left>95) $left=95;
    if ($top>95) $top=95;
    if ($left>3 or $top>4)
    {
        $db->begin();
        $db->query("update ".MAIN_DB_PREFIX."takepos_floor_tables set left_pos=$left, top_pos=$top where name='$place'");
        $db->commit();
    }
    else
    {
        $db->begin();
        $db->query("delete from ".MAIN_DB_PREFIX."takepos_floor_tables where name='$place'");
        $db->commit();
    }
}

if ($action=="updatename")
{
    $db->begin();
    $db->query("update ".MAIN_DB_PREFIX."takepos_floor_tables set name='$after' where name='$place'");
    $db->commit();
}

if ($action=="add")
{
    $db->query("insert into ".MAIN_DB_PREFIX."takepos_floor_tables values ('', '', '', '50', '50', $floor)");
}

// Title
$title='TakePOS - Dolibarr '.DOL_VERSION;
if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $title='TakePOS - '.$conf->global->MAIN_APPLICATION_TITLE;
top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);
?>
<link rel="stylesheet" href="css/pos.css?a=xxx"> 
<style type="text/css">
div.tablediv{
background-image:url(img/table.gif);
-moz-background-size:100% 100%;
-webkit-background-size:100% 100%;
background-size:100% 100%;
height:10%;
width:10%;
text-align: center;
font-size:300%;
color:white;
}
html, body
{
height: 100%;
}
</style>

<script>
var DragDrop='<?php echo $langs->trans("DragDrop"); ?>';
	
function updateplace(idplace, left, top) {
	$.ajax({
		type: "POST",
		url: "floors.php",
		data: { action: "update", left: left, top: top, place: idplace }
		}).done(function( msg ) {
		window.location.reload()
	});
}
	
function updatename(before) {
	var after=$("#"+before).text();
	$.ajax({
		type: "POST",
		url: "floors.php",
		data: { action: "updatename", place: before, after: after }
		}).done(function( msg ) {
		window.location.reload()
		});
	}
	
    
$( document ).ready(function() {
	$.getJSON('./floors.php?action=getTables&zone=<?php echo $floor; ?>', function(data) {
        $.each(data, function(key, val) {
			$('body').append('<div class="tablediv" contenteditable onblur="updatename('+val.label+');" style="position: absolute; left: '+val.left_pos+'%; top: '+val.top_pos+'%;" id="'+val.label+'">'+val.label+'</div>');
			$( "#"+val.label ).draggable(
				{
					start: function() {
					$("#add").attr("src","./img/delete.jpg");
					$("#addcaption").html(DragDrop);
                    },
					stop: function() {
					var left=$(this).offset().left*100/$(window).width();
					var top=$(this).offset().top*100/$(window).height();
					updateplace($(this).attr('id'), left, top);
					}
				}
			);
					
			//simultaneous draggable and contenteditable
			$('#'+val.label).draggable().bind('click', function(){
				$(this).focus();
			})
		});
	});
});

</script>
</head>
<body style="overflow: hidden">
<?php if ($user->admin){?>
<div style="position: absolute; left: 0.1%; top: 0.8%; width:8%; height:11%;">
<?php if ($mode=="edit"){?>
<a onclick="window.location.href='floors.php?mode=edit&action=add';"><?php echo $langs->trans("AddTable"); ?></a>
<?php } else { ?>
<a onclick="window.location.href='floors.php?mode=edit';"><?php echo $langs->trans("Edit"); ?></a>
<?php } ?>
</div>
<?php } 
?>

<div style="position: absolute; left: 25%; bottom: 6%; width:50%; height:3%;">
    <center>
    <h1><img src="./img/arrow-prev.png" width="5%" onclick="location.href='floors.php?floor=<?php if ($floor>1) { $floor--; echo $floor; $floor++;} else echo "1"; ?>';"><?php echo $langs->trans("Floor")." ".$floor; ?><img src="./img/arrow-next.png" width="5%" onclick="location.href='floors.php?floor=<?php $floor++; echo $floor; ?>';"></h1>
    </center>
</div>
</body>
</html>