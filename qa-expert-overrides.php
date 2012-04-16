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
		if($expert){
			$rules['commentbutton'] = true;
			$rules['commentable'] = true;
		}
		return $rules;
	}
						
/*							  
		Omit PHP closing tag to help avoid accidental output
*/							  
						  

