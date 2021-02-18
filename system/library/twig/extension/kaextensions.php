<?php
/*
  Project : Ka Extensions
  Author  : karapuz team <support@ka-station.com>

  Version : 4 ($Revision: 197 $)
  
*/

namespace Twig\Extension;
use Twig\TwigFunction;

class KaExtensions extends AbstractExtension
{
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('t', function($text) {
            	if (class_exists('\KaGlobal')) {
            		return \KaGlobal::t($text);
            	}            	
            	return $text;
            }),
            new TwigFunction('html_entity_decode', function($text) {
        		return html_entity_decode($text);
            }),
            new TwigFunction('get_language_image', function($param) {
            	if (class_exists('\KaGlobal')) {
            		return \KaGlobal::getLanguageImage($param);
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