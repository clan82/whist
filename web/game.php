<?php

require("lib.php");

check_request_method("GET");


$id = check_get_uint($_GET, 'id');
check_input($id);

$controls_positions = check_get_array($_GET, 'cp');
if ($controls_positions === NULL) {
	// Default positions
	$controls_positions = ['bottom'];
} else {
	if (count($controls_positions) > 2) {
		render_unexpected_input_page_and_exit("Too many controls positions!");
	}
}


function game_render_error($key) {
	$data = [
			'unknown_game' => FALSE,
			'bad_rounds' => FALSE,
			'inconsistent_points' => FALSE
	];
	$data[$key] = TRUE;
	render_page("Error", "Error", 'game_error', $data);
	exit;
}


$db_game = db_get_game_with_players($id);
if ($db_game === NULL) {
	game_render_error('unknown_game');
}

$location = $db_game['location'];

$players = [];
$total_points = [];
foreach ($db_game['players'] as $player) {
	$players[] = array_filter_entries($player, "", ["nickname", "fullname"]);
	$total_points[] = $player['total_points'];
}
$n_players = count($players);
$point_rules = &$db_game['point_rules'];



try {
	$db_rounds = db_get_game_rounds($id, $n_players);
} catch (WhistException $ex) {
	error_log($ex);
	game_render_error('bad_rounds');
}


//
// Build round information for each round
//

$acc_total_points = array_fill(0, $n_players, 0);
$rounds = [];

foreach ($db_rounds as $r) {
	$bid_type = $r['bid_type'];
	$data = $r['bid_data'];
	if ($bid_type === "normal") {
		$bid = [
				'tricks' => $data['bid_tricks'],
				'attachment' => $data['bid_attachment']
		];
		$bid_winner_tricks_by_position = [$data['bid_winner_position'] => $data['tricks']];
		$bid_winner_mate_position = $data['bid_winner_mate_position'];
	} else if ($bid_type === "solo") {
		$bid = [
				'solo_type' => $data['type']
		];
		$bid_winner_tricks_by_position = $data['bid_winner_tricks_by_position'];
		$bid_winner_mate_position = NULL;
	} else {
		assert(FALSE);
	}
	$bid['type'] = $bid_type;
	$player_data = [];
	foreach ($r['player_data'] as $position => $data) {
		if ($data['points'] !== NULL) {
			$acc_total_points[$position] += $data['points'];
		}
		$data['acc_points'] = $acc_total_points[$position];
		$player_data[] = $data;
	}
	$round = [
			'index' => $r['round'],
			'dealer_position' => ($r['round'] - 1) % $n_players, // The first round index is 1 and the first player must be the dealer of the first round - TODO allow the user to choose the dealer
			'player_data' => $player_data,
			'bid' => $bid,
			'bid_winner_tricks_by_position' => $bid_winner_tricks_by_position,
			'bid_winner_mate_position' => $bid_winner_mate_position,
			'started_at' => $r['started_at'],
			'ended_at' => $r['ended_at'],
			'updated_at' => $r['updated_at']
	];
	$rounds[] = $round;
}

$n_rounds = count($rounds);



//
// Build begin or end round data, based on the most recent round
//

$active_round = NULL;
if ($n_rounds > 0 && $rounds[$n_rounds - 1]['ended_at'] === NULL) {
	$active_round = $rounds[$n_rounds - 1];
}

if ($active_round === NULL) {
	$controls_view = 'beginround';
	$cancel_view = NULL;
	$legal_attachment_keys = [];
	foreach ($ATTACHMENT_KEY_ORDER as $attachment_key) {
		if (in_array($attachment_key, $REQUIRED_ATTACHMENT_KEYS_ORDER) || in_array($attachment_key, $db_game['attachments'])) {
			$legal_attachment_keys[] = $attachment_key;
		}
	}
	$is_tips_legal = in_array(TIPS, $legal_attachment_keys);
	$tips_count = in_array(POINT_RULE_TIPS, $point_rules);
	$cancel_view_data = NULL;
	$controls_view_data = [
			'is_tips_legal' => $is_tips_legal,
			'tips_count' => $tips_count,
			'legal_attachment_keys' => $legal_attachment_keys
	];
} else {
	$controls_view = 'endround';
	$cancel_view = 'cancelactiveround';
	$bid_type = $active_round['bid']['type'];
	$bid_winner_positions = array_keys($active_round['bid_winner_tricks_by_position']);
	$bye_player_positions = [];
	$participating_player_positions = [];
	foreach ($active_round['player_data'] as $position => $data) {
		if ($data['is_bye']) {
			$bye_player_positions[] = $position;
		} else {
			$participating_player_positions[] = $position;
		}
	}
	$cancel_view_data = [
			'game_id' => &$id
	];
	$controls_view_data = [
			'bid_type' => $bid_type,
			'bid_winner_positions' => $bid_winner_positions,
			'bye_player_positions' => $bye_player_positions,
			'participating_player_positions' => $participating_player_positions
	];
}


/*
  printf("DB:");
  var_dump($db_rounds);
  printf("<p>View:");
  var_dump($rounds);
  printf("</p>");
  printf("<p>TP:");
  var_dump($total_points);
  printf("</p>");
  printf("<p>ACC TP:");
  var_dump($acc_total_points);
  printf("</p>");
 */

// TODO reactivate
// Consistency check
//if ($acc_total_points !== $total_points) {
//	game_render_error('inconsistent_points');
//}

$controls_view_data = array_merge($controls_view_data, [
		'game_id' => &$id,
		'players' => &$players,
		'point_rules' => &$point_rules
				]);


$data = [
		'game_id' => &$id,
		'location' => &$location,
		'players' => &$players,
		'rounds' => &$rounds,
		'total_points' => &$total_points,
		'point_rules' => &$point_rules,
		'cancel_view' => &$cancel_view,
		'cancel_view_data' => &$cancel_view_data,
		'controls_positions' => &$controls_positions,
		'controls_view' => &$controls_view,
		'controls_view_data' => &$controls_view_data
];

$title = "Whist game at " . $location;

render_page($title, $title, "game", $data);
