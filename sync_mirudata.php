<?php
include 'miru_common.php';
$time_start = microtime(true);

$tablename = 'computedNames';
$pk = 'COMPUTED_NAME';
$pairs = json_decode(file_get_contents('https://storage.googleapis.com/mirubot/protic/paddata/miru_data/computed_names.json'), true);
$data = array();
foreach($pairs as $computed_name => $monster_no){
	$data[] = array('COMPUTED_NAME' => $computed_name, 'MONSTER_NO' => $monster_no);
}
$fieldnames = array('COMPUTED_NAME', 'MONSTER_NO');
recreate_table($data, $tablename, $fieldnames, $pk);
populate_table($data, $tablename, $fieldnames);

$dadguide_sql_dump = file_get_contents('https://f002.backblazeb2.com/file/dadguide-data/db/dadguide.mysql');
$miru->conn->multi_query($dadguide_sql_dump);
//Make sure this keeps php waiting for queries to be done
do{} while($miru->conn->more_results() && $miru->conn->next_result());
echo 'Imported dadguide db' . PHP_EOL;

$dungeon_icon_override = json_decode(file_get_contents('.guerrilla/dungeon_icon_overrides.json'), true);
$cond_types = array(
	'dungeon_id' => 'ii',
	'name_na' => 'is',
	'name_jp' => 'is'
);
foreach ($dungeon_icon_override as $override){
	if (strlen($override['icon_id']) == 0){
		trigger_error('Dungeon icon override failed: dungeon_id, name_na, name_jp all empty');
		continue;
	}
	foreach ($cond_types as $cond => $param_types){
		$sql = 'UPDATE dungeons SET icon_id=? WHERE '.$cond.'=?';
		$stmt = $miru->conn->prepare($sql);
		$stmt->bind_param($param_types, $override['icon_id'], $override[$cond]);
		$success = 'Set dungeon icon of '.$override[$cond].' to '.$override['icon_id'];
		if(!$stmt->execute()){
			trigger_error('Dungeon icon override failed: ' . $miru->conn->error);
		} else {
			if ($miru->conn->affected_rows == 0){
				echo 'Dungeon not found by '.$cond.'='.$override[$cond] . PHP_EOL;
			} else {
				echo $success . PHP_EOL;
				break;
			}
		}
	}
}

echo 'Total execution time in seconds: ' . (microtime(true) - $time_start);
?>