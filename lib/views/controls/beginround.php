<?php
global $ATTACHMENTS;
global $SOLO_GAMES, $SOLO_GAME_KEY_ORDER;
global $TIPS_COUNT_MULTIPLIERS;
?>
<form action="beginround.php" method="post">
	<h2>Begin round</h2>
	<input type="hidden" name="game_id" value="<?php echo $game_id ?>" />
	<fieldset>
		<legend>Solo game bid</legend>
		<select name="solo">
			<?php
			option('', 'Solo Type');
			$beats = FIRST_SOLO_GAME_BEATS;
			foreach ($SOLO_GAME_KEY_ORDER as $solo_game_key) {
				$solo_game = $SOLO_GAMES[$solo_game_key];
				$content = sprintf('%s (%s) - beats %s', $solo_game['name'], solo_game_bid_base_points($point_rules, $solo_game), $beats);
				option($solo_game_key, $content);
				$beats++;
			}
			?>
		</select>
	</fieldset>
	<div>Or</div>
	<fieldset>
		<legend>Normal game bid</legend>
		<select name="tricks">
			<?php
			option('', 'Tricks');
			for ($tricks = MIN_BID_TRICKS; $tricks <= MAX_BID_TRICKS; $tricks++) {
				$content = sprintf('%s (%s)', $tricks, normal_game_bid_base_points($point_rules, $tricks));
				option($tricks, $content);
			}
			?>
		</select>
		<select name="attachment">
			<?php
			option('', 'Attachment');
			foreach ($legal_attachment_keys as $attachment_key) {
				$attachment = $ATTACHMENTS[$attachment_key];
				if ($attachment_key === TIPS && $tips_count) {
					//$multiplier = implode(",", $TIPS_COUNT_MULTIPLIERS);
					$multiplier = "?";
				} else {
					$multiplier = $attachment['multiplier'];
				}
				$content = sprintf('%s (x%s)', $attachment['name'], $multiplier);
				option($attachment_key, $content);
			}
			?>
		</select>
		<?php if ($is_tips_legal): ?>
			<select name="tips">
				<?php
				option('', 'Tips');
				for ($tips = MIN_TIPS; $tips <= MAX_TIPS; $tips++) {
					if ($tips_count) {
						$content = sprintf('%s (x%s)', $tips, $TIPS_COUNT_MULTIPLIERS[$tips]);
					} else {
						$content = $tips;
					}
					option($tips, $content);
				}
				?>
			</select>
		<?php else: ?>
			<input type="hidden" name="tips" value="" />
		<?php endif ?>
	</fieldset>
	<fieldset>
		<legend>Bid winners</legend>
		<div>One player for normal games. One or more players for solo games</div>
		<?php foreach ($players as $position => $player): ?>
			<?php multi_checkbox_label("bid_winner_positions", $position, $player['nickname']) ?>:
			<?php multi_checkbox("bid_winner_positions", $position) ?>
		<?php endforeach; ?>
	</fieldset>
	<button type="submit">Begin round</button>
	<div>
		<label>Point rules:</label>
		<?php echo implode(',', $point_rules) ?>
	</div>
</form>
