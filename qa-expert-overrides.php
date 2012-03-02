<?php
		
	function qa_get_permit_options() {
		$permits = qa_get_permit_options_base();
		$permits[] = 'expert_question_roles';
		return $permits;
	}

	function qa_page_q_post_rules($post, $parentpost=null, $siblingposts=null, $childposts=null) {
		$rules = qa_page_q_post_rules_base($post, $parentpost, $siblingposts, $childposts);
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
						  

