<?php

class JFormRuleTesttoken extends JFormRule
{
    public function test(&$element, $value, $group = null, &$input = null, &$form = null)
    {
        return preg_match('/^[0-9A-za-z]{16,32}$/', $value) == 1;
    }
}