<?php
/**
 * Instaclick_Sniffs_WhiteSpace_ControlStructureSpacingSniff.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2012 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Instaclick_Sniffs_WhiteSpace_ControlStructureSpacingSniff.
 *
 * Checks that control structures have the correct spacing around brackets.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2012 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class Instaclick_Sniffs_ControlStructures_ControlStructureSpacingSniff extends PSR2_Sniffs_ControlStructures_ControlStructureSpacingSniff
{
    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (isset($tokens[$stackPtr]['parenthesis_opener']) === true) {
            $parenOpener = $tokens[$stackPtr]['parenthesis_opener'];
            $parenCloser = $tokens[$stackPtr]['parenthesis_closer'];

            if ($tokens[$parenOpener]['code'] === T_OPEN_PARENTHESIS
                && $tokens[$parenOpener + 1]['code'] === T_BOOLEAN_NOT
            ) {
                $phpcsFile->addError('Expected 1 space before exclamation; 0 found', ($parenOpener + 1), 'SpacingBeforeExclamation');
            }

            if ($tokens[$parenOpener]['code'] === T_OPEN_PARENTHESIS
                && $tokens[$parenOpener + 1]['code'] === T_WHITESPACE
                && $tokens[$parenOpener + 2]['code'] === T_BOOLEAN_NOT
            ) {
                $gap = strlen($tokens[($parenOpener + 1)]['content']);
                if ($gap !== 1) {
                    $phpcsFile->addError('Expected 1 space before exclamation; %s found', ($parenOpener + 1), 'SpacingAfterExclamation', array($gap));
                }

                if ($tokens[$parenOpener + 3]['code'] === T_WHITESPACE) {
                    $gap = strlen($tokens[($parenOpener + 3)]['content']);
                    if ($gap !== 1) {
                        $data = array($gap);
                    }
                } else {
                    $data = array(0);
                }

                if (isset($data)) {
                    $phpcsFile->addError('Expected 1 space after exclamation; %s found', ($parenOpener + 3), 'SpacingAfterExclamation', $data);
                }
            }
           
            if ($tokens[($parenOpener + 1)]['code'] === T_WHITESPACE
                && ($tokens[($parenOpener + 2)]['code'] !== T_BOOLEAN_NOT
                || $tokens[$parenOpener]['code'] !== T_OPEN_PARENTHESIS)
            ) {
                $gap   = strlen($tokens[($parenOpener + 1)]['content']);
                $error = 'Expected 0 spaces after opening bracket; %s found';
                $data  = array($gap);
                $phpcsFile->addError($error, ($parenOpener + 1), 'SpacingAfterOpenBrace', $data);
            }

            if ($tokens[$parenOpener]['line'] === $tokens[$parenCloser]['line']
                && $tokens[($parenCloser - 1)]['code'] === T_WHITESPACE
            ) {
                $gap   = strlen($tokens[($parenCloser - 1)]['content']);
                $error = 'Expected 0 spaces before closing bracket; %s found';
                $data  = array($gap);
                $phpcsFile->addError($error, ($parenCloser - 1), 'SpaceBeforeCloseBrace', $data);
            }
        }//end if

    }//end process()


}//end class

?>
