<?php
/**
 * Digraph CMS: DataObject
 * https://github.com/digraphcms/digraph-dataobject

 * Copyright (c) 2017 Joby Elliott <joby@byjoby.com>

 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:

 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 */
namespace Digraph\DataObject\Utils;

/**
 * A very aggressive profanity filter, used in AbstractDataObject to make an
 * attempt at not generating IDs with profanity in them.
 */
class BadWords
{

    protected static $_setup = false;
    protected static $_dict = array();
    protected static $_dictSrc = array();
    protected static $_leet = array();
    protected static $_leetSrc = array();

    /**
     * Check whether a piece of text contains anything that looks like a bad word
     * @param  string $text
     * @return bool
     */
    public static function profane($text)
    {
        if (!static::$_setup) {
            static::init();
        }
        foreach (static::$_dict as $pattern) {
            if (preg_match('/'.$pattern.'/i', $text)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Init class with default words and leet replacements
     * @return void
     */
    protected static function init()
    {
        //add and prep leet replacements
        $leet = explode(PHP_EOL, trim(file_get_contents(__DIR__.'/_resources/leet.txt')));
        foreach ($leet as $replacement) {
            $replacement = explode(' ', $replacement);
            static::addLeet($replacement[0], $replacement[1], true);
        }
        static::prepLeet();
        //add and prep dictionary
        foreach (explode(PHP_EOL, trim(file_get_contents(__DIR__.'/_resources/badwords.txt'))) as $word) {
            static::addWord($word, true);
        }
        static::prepDict();
    }

    /**
     * add a Leet replacement
     * @param string  $a        original
     * @param string  $b        replacement
     * @param boolean $skipPrep whether to run prepLeet afterwards
     */
    public static function addLeet($a, $b, $skipPrep = false)
    {
        if (!isset(static::$_leet[$a])) {
            static::$_leetSrc[$a] = array($a=>$a);
        }
        static::$_leetSrc[$a][$b] = $b;
        if (!$skipPrep) {
            static::prepLeet();
        }
    }

    /**
     * parse added Leet rules into a set of regex rules
     * @return void
     */
    public static function prepLeet()
    {
        static::$_leet = array();
        foreach (static::$_leetSrc as $key => $value) {
            static::$_leet[$key] = '('.implode('|', $value).')';
        }
    }

    /**
     * add a profane word
     * @param string  $word
     * @param boolean $skipPrep whether to run prepDict afterwards
     */
    public static function addWord($word, $skipPrep = false)
    {
        static::$_dictSrc[] = $word;
        static::$_dictSrc = array_unique(static::$_dictSrc);
        if (!$skipPrep) {
            static::prepDict();
        }
    }

    /**
     * parse added words and Leet rules into a set of regex rules
     * @return void
     */
    public static function prepDict()
    {
        static::$_dict = array();
        foreach (static::$_dictSrc as $word) {
            foreach (static::$_leet as $a => $b) {
                $word = str_replace($a, $b, $word);
            }
            static::$_dict[] = $word;
        }
    }
}
