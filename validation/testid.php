<?php

class JFormRuleTestid extends JFormRule
{
    public function test(&$element, $value, $group = null, &$input = null, &$form = null)
    {
        return preg_match('/^[0-9]{6}$/', $value) == 1;

    }
}