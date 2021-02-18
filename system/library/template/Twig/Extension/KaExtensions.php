<?php
/*
  Project : Ka Extensions
  Author  : karapuz <support@ka-station.com>

  Version : 4 ($Revision: 197 $)
  
*/

class Twig_Extension_KaExtensions extends Twig_Extension
{
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('t', function($text) {
            	if (class_exists('\KaGlobal')) {
            		return KaGlobal::t($text);
            	}            	
            	return $text;
            }),
            new \Twig_SimpleFunction('html_entity_decode', function($text) {
        		return html_entity_decode($text);
            }),
            new \Twig_SimpleFunction('get_language_image', function($param) {
            	if (class_exists('\KaGlobal')) {
            		return KaGlobal::getLanguageImage($param);
            	}
            	return '';
            }),
        );
    }
    
    public function getName()
    {
        return 'Ka Extensions';
    }
}