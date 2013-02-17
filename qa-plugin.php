<?php
        
/*              
        Plugin Name: Expert Questions
        Plugin URI: https://github.com/NoahY/q2a-expert
        Plugin Update Check URI: https://raw.github.com/NoahY/q2a-expert/master/qa-plugin.php
        Plugin Description: Ask expert questions
        Plugin Version: 2.2
        Plugin Date: 2011-09-05
        Plugin Author: NoahY
        Plugin Author URI:                              
        Plugin License: GPLv2                           
        Plugin Minimum Question2Answer Version: 1.4
*/                      
                        
                        
        if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
                        header('Location: ../../');
                        exit;   
        }               

        qa_register_plugin_module('module', 'qa-expert-admin.php', 'qa_expert_question_admin', 'Expert Admin');
        qa_register_plugin_module('event', 'qa-expert-check.php', 'qa_expert_question_event', 'Expert Event');

        qa_register_plugin_module('page', 'qa-expert-page.php', 'qa_expert_question_page', 'Expert Questions Page');
        
        qa_register_plugin_layer('qa-expert-layer.php', 'Expert Layer');

        if(function_exists('qa_register_plugin_phrases')) {
            qa_register_plugin_overrides('qa-expert-overrides.php');
            qa_register_plugin_phrases('qa-expert-lang-*.php', 'expert_question');
        }                       
                        
/*                              
        Omit PHP closing tag to help avoid accidental output
*/                              
                          

