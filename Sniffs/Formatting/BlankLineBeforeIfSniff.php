<?php

/**
 * This file is part of the Instaclick coding standard
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer-Instaclick
 * @author   Anthon Pang <apang@softwaredevelopment.ca>
 * @license  http://spdx.org/licenses/MIT MIT License
 * @version  GIT: master
 * @link     https://github.com/instaclick/CodingStandard
 */

/**
 * Instaclick_Sniffs_Formatting_BlankLineBeforeIfSniff.
 *
 * Throws error if there is no blank line before if statements.
 *
 * @category PHP
 * @package  PHP_CodeSniffer-Instaclick
 * @author   Dave Hauenstein <davehauenstein@gmail.com>
 * @license  http://spdx.org/licenses/MIT MIT License
 * @link     https://github.com/opensky/Symfony2-coding-standard
 */
class Instaclick_Sniffs_Formatting_BlankLineBeforeIfSniff implements PHP_CodeSniffer_Sniff
{
    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = array(
        'PHP',
    );

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_IF);
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile All the tokens found in the document.
     * @param int                  $stackPtr  The position of the current token in
     *                                        the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens          = $phpcsFile->getTokens();
        $current         = $stackPtr;
        $previousLine    = $tokens[$stackPtr]['line'] - 1;
        $prevLineTokens  = array();

        while ($current >= 0 && $tokens[$current]['line'] >= $previousLine) {
            if ($tokens[$current]['line'] == $previousLine
                && $tokens[$current]['type'] !== 'T_WHITESPACE'
                && $tokens[$current]['type'] !== 'T_DOC_COMMENT'
                && $tokens[$current]['type'] !== 'T_COMMENT'
            ) {
                $prevLineTokens[] = $tokens[$current]['type'];
            }
            $current--;
        }

        if (isset($prevLineTokens[0])
            && ($prevLineTokens[0] === 'T_OPEN_CURLY_BRACKET'
            || $prevLineTokens[0] === 'T_COLON')
        ) {
            return;
        }

        if (count($prevLineTokens) > 0) {
            $phpcsFile->addError(
                'Missing blank line before if statement',
                $stackPtr,
                'BlankLineBeforeIf'
            );
        }

        return;
    }
}
