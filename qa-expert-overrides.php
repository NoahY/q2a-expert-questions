<?php
		
	function qa_get_permit_options() {
		$permits = qa_get_permit_options_base();
		$permits[] = 'expert_question_roles';
		return $permits;
	}
						
/*							  
		Omit PHP closing tag to help avoid accidental output
*/							  
						  

