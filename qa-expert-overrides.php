<?php
		
	function qa_get_permit_options() {
		$permits = qa_get_permit_options_base();
		$permits[] = 'expert_question_roles';
		return $permits;
	}
	

	
	function qa_page_q_post_rules($post, $parentpost=null, $siblingposts=null, $childposts=null) {
		$rules = qa_page_q_post_rules_base($post, $parentpost, $siblingposts, $childposts);
		qa_db_query_sub(
			'CREATE TABLE IF NOT EXISTS ^postmeta (
			meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			post_id bigint(20) unsigned NOT NULL,
			meta_key varchar(255) DEFAULT \'\',
			meta_value longtext,
			PRIMARY KEY (meta_id),
			KEY post_id (post_id),
			KEY meta_key (meta_key)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8'
		);		
		$expert = qa_db_read_one_value(
			qa_db_query_sub(
				"SELECT meta_value FROM ^postmeta WHERE meta_key='is_expert_question' AND post_id=#",
				$post['postid']
			), true
		);
		if($expert) {

			if(!qa_permit_value_error(qa_opt('expert_question_roles'), qa_get_logged_in_userid(), qa_get_logged_in_level(), qa_get_logged_in_flags()))
				$is_expert = true;
			
			$users = qa_opt('expert_question_users');
			$users = explode("\n",$users);
			$handle = qa_get_logged_in_handle();
			foreach($users as $idx => $user) {
				if ($user == $handle) {
					$is_expert = true;
					break;
				}
				if(strpos($user,'=')) {
					$user = explode('=',$user);
					if($user[0] == $handle) {
						$catnames = explode(',',$user[1]);
						$cats = qa_db_read_all_values(
							qa_db_query_sub(
								'SELECT categoryid FROM ^categories WHERE title IN ($)',
								$catnames
							)
						);
						$is_expert = $cats;
					}
				}
			}
			if(isset($is_expert) && !$rules['viewable']) { // experts that aren't allowed to change hidden questions
				if(is_array($is_expert)) {
					$in_cats = qa_db_read_one_value(
						qa_db_query_sub(
							"SELECT COUNT(postid) FROM ^posts WHERE categoryid IN (#) AND postid=#",
							$is_expert,$post['postid']
						), true
					);
					if($in_cats)
						$rules['viewable'] = true;
						
				}
				else 
					$rules['viewable'] = true;
			}

			$rules['reshowable'] = false;
			$rules['answerbutton'] = true;
			$rules['commentbutton'] = true;
			$rules['commentable'] = true;
		}
		return $rules;
	}
						
/*							  
		Omit PHP closing tag to help avoid accidental output
*/							  
						  

