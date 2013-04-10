<?php
/**
 * Instaclick_Sniffs_Namespaces_NamespaceStructureSniff.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Anthon Pang <apang@softwaredevelopment.ca>
 * @copyright 2013 Instaclick Inc.
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Instaclick_Sniffs_Namespaces_NamespaceStructureSniff.
 *
 * Ensures namespaces are declared correctly.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Anthon Pang <apang@softwaredevelopment.ca>
 * @copyright 2013 Instaclick Inc.
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class Instaclick_Sniffs_Namespaces_NamespaceStructureSniff implements PHP_CodeSniffer_Sniff
{
    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_NAMESPACE);

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in
     *                                        the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $namespace = '';

        for ($i = $stackPtr + 1; $i < $phpcsFile->numTokens; $i++) {
            switch ($tokens[$i]['code']) {
                case T_STRING:
                case T_NS_SEPARATOR:
                    $namespace .= $tokens[$i]['content'];
                case T_WHITESPACE:
                    continue 2;
            }

            break;
        }

        $filename = $phpcsFile->getFilename();
        $synthesizedName = str_replace('\\', '/', $namespace) . '/' . basename($filename);

        // namespace must be convertable to match directory structure
        if (substr($synthesizedName, 0, 3) === 'IC/'
            && strpos($filename, $synthesizedName) === false
        ) {
            $error = 'Namespace doesn\'t follow PSR-0 requirements';
            $phpcsFile->addError($error, $stackPtr, 'NamespaceStructure');
        }

    }//end process()

}//end class

?>
