<?php
/*
	Plugin Name:	Pollka polls
	Plugin URI:	    http://wordpress.unicornis.pl
	Description:	Simple and flexible polls for WordPress and bbPress.
	Author:		    Unicornis
	Author URI:	    http://wordpress.unicornis.pl/
    Text Domain:    Pollka
	Version:	    1.14
*/
class wp_polls {
	function __construct() {
		global $wpdb;

		$this->db_version = '1.15';
		$this->votes_table_name = $wpdb->prefix . "poll_votes";
                $this->polls_table_name = $wpdb->prefix . "polls";
		$this->poll_count = 0;
		$this->opts = array(
                        'shortcode'    => __('poll','pollka'),
                        'question_tag' => __('question','pollka'),
                        'answers_tag'  => __('answers','pollka'),
                        'defaults'     => __('Yes,No','pollka'),
       	       	       	'options_tag'  => __('options','pollka'),
                        'secret_tag'   => __('secret','pollka'),
                        'secret_dflt'  => 'off',
                        'public_tag'   => __('public','pollka'),
                        'public_dflt'  => 'off',
                        'open_tag'     => __('open','pollka'),
                        'open_dflt'    => 'off',
                        'time_tag'     => __('time','pollka'),
                        'time_dflt'    => '',
                        'summary_tag'  => __('summary','pollka'),
                        'summary_dflt' => 'off'
		);
		$this->opts = (array)get_option('poll_options') + $this->opts;

		register_activation_hook(__FILE__, array(&$this, 'activate'));
		register_deactivation_hook(__FILE__, array(&$this, 'deactivate'));
		add_action('plugins_loaded', array(&$this, 'update_db_check'));
		add_action('init', array(&$this, 'initialise'));
		add_action('admin_menu', array(&$this, 'option_menu'));
		add_shortcode($this->opts['shortcode'], array(&$this, 'poll'));
		add_action('wp_ajax_poll_vote', array(&$this, 'poll_ajax_voting'));
	}

	// Plugin activation.
	function activate() {
		if(!current_user_can('activate_plugins')) return;

		// Create new capability for managing polls.
		$role = get_role('administrator');
		$role->add_cap('manage_polls');

		// Check if we need to install or upgrade the DB tables.
		if(get_option('polls_db_version') != $this->db_version) {
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

			$ddl = "CREATE TABLE " . $this->votes_table_name . " (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				post_id bigint NOT NULL,
				poll_id int(3) NULL DEFAULT 1,
				user_id bigint NOT NULL,
				vote_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				vote int(2) NOT NULL,
				vote_option varchar(20) NULL,
				PRIMARY KEY  (id),
				KEY user (user_id),
				KEY post (post_id)
			) ENGINE = INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;";
			dbDelta($ddl);
                        $ddl = "CREATE TABLE " . $this->polls_table_name . " (
                                id bigint(20) NOT NULL AUTO_INCREMENT,
                                post_id bigint NOT NULL,
                                poll_id int(3) NULL DEFAULT 1,
                                showVoters int(3) DEFAULT 0,
                                showByVote int(3) DEFAULT 0,
                                startTime timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                endTime timestamp NULL DEFAULT NULL,
                                pollOptions varchar(200) NULL,
                                PRIMARY KEY  (id),
                                KEY post (post_id),
                                KEY user (poll_id)
                        )ENGINE = INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;";
                        dbDelta($ddl);


			// Remeber the version of the plugin the checkout DB was setup with for upgrades.
			update_option("polls_db_version", $this->db_version);
		}
	}

	// Plugin de-activation.
	function deactivate() {
		if(!current_user_can('activate_plugins')) return;
	}

	// Checkout DB tables update...
	function update_db_check() {
		$this->activate();
	}
	// Initialisation...
	function initialise() {
                load_plugin_textdomain('pollka', false, dirname(plugin_basename(__FILE__)));
		// Get options...
		$this->opts = (array)get_option('poll_options') + $this->opts;
		// Add quicktag for Poll insertion (different for bbPress and WordPress).
		if(is_admin()) {
			add_action('admin_print_footer_scripts', array(&$this, 'add_quicktags'));
		}
		else {
			add_action('bbp_footer', array(&$this, 'add_quicktags'));
		}

		// Allow bbPress Polls?
		if(shortcode_exists('bbp-forum-index')) {
			add_filter('bbp_get_reply_content', array(&$this, 'bb_poll'), 10, 2);
		}
	}

	// bbPress polls...
	function bb_poll($content, $reply_id) {
		$pattern = get_shortcode_regex();
		if(preg_match_all('/' . $pattern . '/s', $content, $matches) && array_key_exists(2, $matches) && in_array($this->opts['shortcode'], $matches[2])) {
			foreach($matches[0] as $match) {
				$content = str_replace($match, do_shortcode($match), $content);
			}
		}
		return $content;
	}

	// User data...
	function get_voter_details($id, $poll,$num_votes,$showVoters,$byVoters) {
		global $wpdb, $current_user;
		$data = $wpdb->get_results($wpdb->prepare(
			"select user_id id, vote from {$this->votes_table_name} where post_id = %d and poll_id = %d",
			$id,
			$poll
		));
		//var_dump($num_votes);
                $out = sprintf(_n('1 vote', '%d votes', $num_votes,'pollka'),$num_votes); 
		$first = true;
                if ($showVoters=="1")
		   foreach($data as $user) {
			$user_info = get_userdata($user->id);
			$out .= sprintf("%s<span class='poll_voter'>%s</span>%s",
				($first ? ': ' : ', '),
				($user->id == $current_user->ID ? __('myself','pollka') : $user_info->display_name),
				($byVoters && $user->vote != '' ? ' (' . $user->vote . ')' : '')
			);
			$first = false;
		   } 

		return $out;
	}
	// Poll shortcode...
	function poll($atts) {
		global $post, $current_user, $wpdb;
                date_default_timezone_set(get_option('timezone_string'));
		// ID for this set of poll options.
		$this->poll_count++;

		// Enqueue the scripts for polls.
		wp_enqueue_script('pollka', WP_PLUGIN_URL . '/pollka/pollka.js', array('jquery'));
		wp_localize_script( 'pollka', 'objectL10n', array(
	              'Poll' => __( 'Poll', 'pollka' ),
                      'Question' => __('Question','pollka'),
                      'Poll for what?' => __('Poll for what?','pollka'),
                      'Answers (comma separated)' => __('Answers (comma separated)','pollka')
                ) );
                
                wp_enqueue_style('pollka', WP_PLUGIN_URL . '/pollka/pollka.css');
		wp_localize_script('pollka', 'ajaxurl', admin_url('admin-ajax.php'));
                $options_dflts='';
                if ($this->opts['public_dflt']==='on') $options_dflts.=$this->opts['public_tag'].',';
                if ($this->opts['secret_dflt']==='on') $options_dflts.=$this->opts['secret_tag'].',';
                if ($this->opts['open_dflt']==='on') $options_dflts.=$this->opts['open_tag'].',';
                if ($this->opts['summary_dflt']==='on') $options_dflts.=$this->opts['summary_tag'].',';   
		// Extract the shortcode parameters.
                $sc_tags=array();
                $sc_tags[$this->opts['question_tag']]='';
                $sc_tags[$this->opts['answers_tag']]=$this->opts['defaults'];
                $sc_tags[$this->opts['options_tag']]=$options_dflts;
                $sc_tags[$this->opts['time_tag']]=$this->opts['time_dflt'];;
                $sc_params=shortcode_atts($sc_tags,$atts);
                $showVoters=1;
                if(strpos($sc_params[$this->opts['options_tag']],$this->opts['public_tag'])!==false)
                   $showByVote=$showVoters=1;
                if(strpos($sc_params[$this->opts['options_tag']],$this->opts['secret_tag'])!==false)
                   $showByVote=$showVoters=0;
                if(strpos($sc_params[$this->opts['options_tag']],$this->opts['open_tag'])!==false)
                   $open=1;
                if(strpos($sc_params[$this->opts['options_tag']],$this->opts['summary_tag'])!==false)
                   $showSummary=1;
                $poll_params = $wpdb->get_row($wpdb->prepare(
                        "select * from {$this->polls_table_name} where post_id = %d and poll_id = %d",
                        $post->ID, $this->poll_count));
                $poll_ID=$poll_params->id;
                //echo "Post: ".$post->ID." Poll: ".$this->poll_count." Poll params: ".$poll_params->pollOptions;
                if (!$poll_ID) {  // new poll, need to calculate the end time and create the option string
                     $endTime=NULL;;
                     if ($sc_params[$this->opts['time_tag']]){
                        $endTime=strtotime($sc_params[$this->opts['time_tag']]);
                     }
                     $optionsStr='';
                     foreach ($atts as $key => $value) 
                        $optionsStr.= $key .' = "'.$value.'" ';
                }
                else {
                    $endTime=strtotime($poll_params->endTime);
                    //$optionsStr=$poll_params->pollOptions;
                    $optionsStr='';
                     foreach ($atts as $key => $value)
                        $optionsStr.= $key .' = "'.$value.'" ';
                }
                if ($endTime<0)
                   $endTime=NULL;
                $now=time(); 
                // Insert or Update the poll parameters on this post.
                if (!$poll_ID || $poll_params->showVoters != $showVoters || $poll_params->showByVote != $showByVote) {
                    $wpdb->query($wpdb->prepare("insert into {$this->polls_table_name} 
                                         (id,post_id,poll_id,showVoters,showByVote,startTime,endTime,pollOptions) values (%d,%d,%d,%d,%d,FROM_UNIXTIME(%d),FROM_UNIXTIME(%d),%s)
                                         on duplicate key update
                                          id=%d, post_id=%d, poll_id=%d, showVoters=%d, showByVote=%d, startTime=FROM_UNIXTIME(%d), endTime=FROM_UNIXTIME(%d),pollOptions=%s",
                                          $poll_ID,$post->ID,$this->poll_count,$showVoters,$showByVote,$now,$endTime,$optionsStr,
                                          $poll_ID,$post->ID,$this->poll_count,$showVoters,$showByVote,$now,$endTime,$optionsStr));
                }
		// SQL to get the current user's vote, if they have one.
		$usr_vote = $wpdb->get_var($wpdb->prepare(
			"select vote from {$this->votes_table_name} where post_id = %d and poll_id = %d and user_id = %d",
			$post->ID,
			$this->poll_count,
			$current_user->ID
		));
		$voted = ($usr_vote != '');
		if ($open)
		   $canVote=1;
		else 
		   $canVote=!$voted;
		
		if ($endTime!=0 && $endTime<$now)
		   $canVote=0;
		else
		   $showSummary=0; // Do not show results while the poll is still open
                $showResults = ($voted || $showSummary);
		// Check status...
		if(function_exists('is_bbpress')) {
			if(is_bbpress()) {
				switch($post->post_type) {
					case 'topic':
						if(bbp_get_topic_status($post->ID) != 'publish') {
							$canVote = 1;
						}
						break;
					default: date_default_timezone_set('UTC');
                                                 //break; 
                                                 return '[ankieta '.$optionsStr.']';
				}
			}
		}

		// SQL to get the total number of votes.
		$num_votes = $wpdb->get_var($wpdb->prepare(
			"select count(*) from {$this->votes_table_name} where post_id = %d and poll_id = %d",
			$post->ID,
			$this->poll_count
		));

		// Start building output.
                $out = sprintf('<div class="poll_container" id="poll-%s">', $this->poll_count);
		
                // Voting button.
		if($canVote) {
			$out .=  sprintf('<input poll_group="poll-%s" poll_id="%s" poll_canVote="%s" name="button_poll-%s" class="poll_vote_button" type="button" value="%s"/>',
				$this->poll_count,
                                $post->ID,
                                $canVote,
                                $this->poll_count,
                                __('Vote','pollka')
			);
		}
		$out .= '<span class="poll_question">' . $sc_params[$this->opts['question_tag']] . '</span>';
                if ($endTime!=0){
		   $out.='<span class="poll_endTime">'.__('Poll open until ','pollka') . date(get_option('date_format').' '.get_option('time_format'),$endTime) . '<br></span>';
		}
                foreach(explode(",", $sc_params[$this->opts['answers_tag']]) as $i => $opt) {
			$i++;

			$votes = $wpdb->get_var($wpdb->prepare(
				"select count(*) from {$this->votes_table_name} where post_id = %d and poll_id = %d and vote = %d",
				$post->ID,
				$this->poll_count,
				$i
			));

			$out .= $i.". ".sprintf('<span class="poll_bar_empty" style="display: %s;"><span class="poll_bar_full" style="display: %s; width: %s%%;" id="%s"></span></span>',
				($showResults  ? 'inline-block' : 'none'),
				($showResults ? 'block' : 'none'),
				($votes > 0 ? round($num_votes > 0 ? 100 * $votes / $num_votes : 0) : 0),
				'bar-bar-' . $post->ID . '-' . $this->poll_count . '-' . $i
			);
			
			$out .= sprintf('<span class="poll_option"><input type="radio" value="%s" name="%s" %s %s vote_option="%s" /> %s</span> <span class="poll_votes" style="display: %s;" id="%s">(%s)</span> <br />',
				$i,
				'poll-' . $this->poll_count,
				($usr_vote == $i ? 'checked' : ''),
				(!$canVote ? 'disabled' : ''),
				$opt,
				$opt,
				(!$canVote ? 'none' : 'none'),
				'bar-cnt-' . $post->ID . '-' . $this->poll_count . '-' . $i,
				$votes
			);
		}

		// Show voters...
			$out .= sprintf("<p class='poll_voter_list' style='display: %s;'>%s</p>",
				($showResults ? 'block' : 'none'),
				$this->get_voter_details($post->ID, $this->poll_count,$num_votes,$showVoters,$showByVote)
			);
                date_default_timezone_set('UTC');
		return $out . '</div>';
	}

	// Handle AJAX for poll voting.
	function poll_ajax_voting() {
		global $wpdb, $current_user;

		// Sanitize the POSTed data.
		$vote = sanitize_text_field($_POST['value']);
		$vote_option = sanitize_text_field($_POST['vote_option']);
                $post_id = sanitize_text_field($_POST['id']);
		$poll = substr(strrchr(sanitize_text_field($_POST['poll']), "-"), 1);
		// Get the ID of a pre-exisitng vote for this user and post.

		$vote_id = $wpdb->get_var($wpdb->prepare(
			"select id from {$this->votes_table_name} where post_id = %d and poll_id = %d and user_id = %d",
			$post_id,
			$poll,
			$current_user->ID
		));

		// Insert or Update the vote for this user on this post.
		$wpdb->replace(
			$this->votes_table_name,
			array(
				'id' => $vote_id,
				'vote' => $vote,
				'post_id' => $post_id,
				'poll_id' => $poll,
				'user_id' => $current_user->ID,
				'vote_option' => $vote_option
			),
			array('%d', '%d', '%s', '%d', '%d', '%s')
		);

		// Return vote data to allow UI update.
		$data = $wpdb->get_results($wpdb->prepare(
			"select vote, count(vote) cnt from {$this->votes_table_name} where post_id = %d and poll_id = %d group by vote",
			$post_id,
			$poll
		));

		// SQL to get the total number of votes.
		$num_votes = $wpdb->get_var($wpdb->prepare(
			"select count(*) from {$this->votes_table_name} where post_id = %d and poll_id = %d",
			$post_id,
			$poll
		));
                $showParams =  $wpdb->get_row($wpdb->prepare(
                        "select * from {$this->polls_table_name} where post_id = %d and poll_id = %d",
                        $post_id,
                        $poll
                ));
                
		// AJAX response...
		echo "<update>\n";
		foreach($data as $v) {
			printf("<vote id='%s' poll='%s' cnt='%s'>%s</vote>\n",
				$v->vote,
				$poll,
				$v->cnt,
				round($v->cnt > 0 ? 100 * $v->cnt / $num_votes : 0)
			);
		}
		printf("<voters>%s</voters>\n", htmlentities($this->get_voter_details($post_id, $poll,$num_votes,$showParams->showVoters,$showParams->showByVote), ENT_QUOTES));
		echo "</update>\n";

		die();
	}

	// Poll quicktag.
	function add_quicktags() {
            ?> 

		<script type="text/javascript">
			jQuery(document).ready(function() {
				if(typeof QTags != "undefined") {
					function insert_poll() {
                                                <?php 
                                                      $shortcode = $this->opts['shortcode'];
                                                      $question = $this->opts['question_tag'];
                                                      $answers  = $this->opts['answers_tag'];
                                                      $defaults = $this->opts['defaults'];
                                                      $question_prompt = __("Poll question","pollka");
                                                      $question_example = __("Your poll question goes here","pollka");
                                                      $answers_prompt = __("Answers (comma separated)","pollka");
                                                      $buttonText = __("Poll","pollka");
 
                                                ?>
						QTags.insertContent('[<?php echo $shortcode; ?>');
						var q = prompt('<?php echo $question_prompt ;?>', '<?php echo $question_example ;?>');
						QTags.insertContent(' <?php echo $question;?>');
                                                QTags.insertContent("='" + q + "'");
						var a = prompt('<?php echo $answers_prompt; ?>','<?php echo $defaults; ?>');
						QTags.insertContent(' <?php echo $answers; ?>');
                                                QTags.insertContent("='" + a + "'");
						QTags.insertContent(']');
					}
					QTags.addButton('poll', '<?php echo $buttonText; ?>', insert_poll);
				}
			});
		</script>
	<?php   
	}

	// Options menu.
	function option_menu() {
		add_options_page('Poll Options', 'Pollka Polls', 'manage_options', 'manage-poll-options', array(&$this, 'manage_poll_options'));
	}

	// Options page.
	function manage_poll_options() {	
		if(!current_user_can('manage_options')) wp_die(__('Sorry, you do not have permissions to manage options.','pollka'));

		if(isset($_POST['option-submit'])) {
                        
			$options_update = array (
				'shortcode'    => $_POST['shortcode'],
                                'question_tag' => $_POST['question'],
                                'answers_tag'  => $_POST['answers'],
                                'defaults'     => $_POST['defaults'], 
                                'options_tag'  => $_POST['options'],
                                'secret_tag'   => $_POST['secret'],
                                'secret_dflt'  => $_POST['secret_dflt'],
                                'public_tag'   => $_POST['public'],
                                'public_dflt'  => $_POST['public_dflt'],
                                'open_tag'     => $_POST['open'],
                                'open_dflt'    => $_POST['open_dflt'], 
                                'time_tag'     => $_POST['time'],
                                'time_dflt'    => $_POST['time_dflt'],
                                'summary_tag'  => $_POST['summary'],
                                'summary_dflt' => $_POST['summary_dflt']  
			);
			update_option('poll_options', $options_update);
		}
		$this->opts = (array)get_option('poll_options') + $this->opts;

		echo '<div class="wrap">';
		printf("<img src='%s/pollka/images/pollka.png' style='float: left; margin: 12px 12px 0 0;'/>", WP_PLUGIN_URL);
		echo '<h2>';
                _e('Pollka Poll Options','pollka');
                echo'</h2>';
		_e('Control the behaviour of the Pollka polls plugin.','pollka');
                echo '<br />';
		printf('<form method="post" action="%s?page=manage-poll-options&updated=true">', $_SERVER['PHP_SELF']);
		echo '<table class="form-table">';
		echo '<tr><td>';
                _e('Tag','pollka');
                echo '</td><td>';
                _e('Value','pollka');
                echo '</td><td>';
                _e('Default','pollka');
                echo '</td><td>';
                _e('Description','pollka');
                echo '</td></tr><tr><td>';
                _e('Shortcode','pollka');
                echo '</td>';
		printf('<td>   <input type="text" name="shortcode" value="%s" size="10" />', $this->opts['shortcode']);
		echo '</td><td></td><td><small>';
                _e('Allow for interoperability with other plugins using the same shortcode or to localize shotdcode to local language','pollka');
		echo '</small></td></tr><tr><td>';
                echo '<tr><td>';
                _e('Question tag','pollka');
                printf('</td><td> <input type="text" name="question" value="%s" size="10" />', $this->opts['question_tag']);
                echo '</td><td></td><td><small>';
                _e('Question tag in a shortcode.','pollka');
                echo '</small></td></tr><tr><td>';
                _e('Answers tag','pollka');
                printf('</td><td> <input type="text" name="answers" value="%s" size="10" />', $this->opts['answers_tag']);
                echo '</td><td></td><td><small>';
                _e('Answers tag in a shortcode.','pollka');
                echo '</small></td></tr><td>';
                _e('Default answers','pollka');
                printf('</td><td> <input type="text" name="defaults" value="%s" size="10" />', $this->opts['defaults']);
                echo '</td><td></td><td><small>';
                _e('Default answers.','pollka');
                echo '</small></td></tr><tr><td>';
                _e('Options tag','pollka');
                printf('</td><td> <input type="text" name="options" value="%s" size="10" />', $this->opts['options_tag']);
                echo '</td><td></td><td><small>';
                _e('Options tag in a shortcode.','pollka');
                echo '</small></td></tr><tr><td><tr><td>';
                _e('Secret tag','pollka');
                printf('</td><td> <input type="text" name="secret" value="%s" size="10" />', $this->opts['secret_tag']);
                echo '</td><td>';
                printf('<input type="checkbox" name="secret_dflt" %s />', $this->opts['secret_dflt'] == 'on' ? 'checked' : '');
                echo '</td><td><small>';
                _e('Secret voting tag in a shortcode. Poll results will not show who has voted.','pollka');
                echo '</small></td></tr><tr><td>';
                _e('Public tag','pollka');
                printf('</td><td> <input type="text" name="public" value="%s" size="10" />', $this->opts['public_tag']);
                echo '</td><td>';
                printf('<input type="checkbox" name="public_dflt" %s />', $this->opts['public_dflt'] == 'on' ? 'checked' : '');
                echo '</td><td><small>';
                _e('Public poll tags in a shortcode. Poll results will show choice by each voter.','pollka');
                echo '</small></td></tr><tr><td>';
                _e('Time tag','pollka');
                printf('</td><td> <input type="text" name="time" value="%s" size="10" />', $this->opts['time_tag']);
                echo '</td><td></td><td><small>';
                _e('Time tag in a shortcode.','pollka');
                echo '</small></td></tr><tr><td>';
                _e('Default poll duration','pollka');
                printf('</td><td> <input type="text" name="time_dflt" value="%s" size="10" />', $this->opts['time_dflt']);
                echo '</td><td></td><td><small>';
                _e('Default poll duration (strtotime format). Empty = forever.','pollka');
                echo '</small></td></tr><tr><td>';
                _e('Open tag','pollka');
                printf('</td><td> <input type="text" name="open" value="%s" size="10" />', $this->opts['open_tag']);
                echo '</td><td>';
                printf('<input type="checkbox" name="open_dflt" %s />', $this->opts['open_dflt'] == 'on' ? 'checked' : '');
                echo '</td><td><small>';
                _e('Open tag in a shortcode. Voting can be repeated.','pollka');
                echo '</small></td></tr><tr><td>';
                _e('Summary','pollka');
                printf('</td><td> <input type="text" name="summary" value="%s" size="10" />', $this->opts['summary_tag']);
                echo '</td><td>';
                printf('<input type="checkbox" name="summary_dflt" %s />', $this->opts['summary_dflt'] == 'on' ? 'checked' : '');
                echo '</td><td><small>';
                _e('Summary tag in a shortcode. Show summary for the time constrained poll when the time is up.','pollka');
                echo '</small></td></tr>';
		echo '</table><p class="submit"><input type="submit" class="button-primary" name="option-submit" value="';
                _e('Update Options','pollka');
                echo '" /></p>';
		echo '</form>';
		echo '</div>';
	}
}

$polls = new wp_polls();
?>
