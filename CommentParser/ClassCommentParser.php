<?php
/**
 * Parses Class doc comments.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006-2012 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

if (class_exists('PHP_CodeSniffer_CommentParser_AbstractParser', true) === false) {
    $error = 'Class PHP_CodeSniffer_CommentParser_AbstractParser not found';
    throw new PHP_CodeSniffer_Exception($error);
}

/**
 * Parses Class doc comments.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006-2012 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class Instaclick_CodeSniffer_CommentParser_ClassCommentParser extends PHP_CodeSniffer_CommentParser_AbstractParser
{
    /**
     * The author elements of this class.
     *
     * @var array(SingleElement)
     */
    private $_authors = array();

    /**
     * The group elements of this class.
     *
     * @var array(SingleElement)
     */
    private $_groups = array();

    /**
     * Returns the allowed tags withing a class comment.
     *
     * @return array(string => int)
     */
    protected function getAllowedTags()
    {
        return array(
            'author' => false,
            'group'  => false,
        );

    }

    /**
     * Parses the author tag of this class comment.
     *
     * @param array $tokens The tokens that comprise this tag.
     *
     * @return array(PHP_CodeSniffer_CommentParser_SingleElement)
     */
    protected function parseAuthor($tokens)
    {
        $author = new PHP_CodeSniffer_CommentParser_SingleElement(
            $this->previousElement,
            $tokens,
            'author',
            $this->phpcsFile
        );

        $this->_authors[] = $author;

        return $author;

    }

    /**
     * Parses the group tags of this class comment.
     *
     * @param array $tokens The tokens that comprise this tag.
     *
     * @return PHP_CodeSniffer_CommentParser_SingleElement
     */
    protected function parseGroup($tokens)
    {
        $group = new PHP_CodeSniffer_CommentParser_SingleElement(
            $this->previousElement,
            $tokens,
            'group',
            $this->phpcsFile
        );

        $this->_groups[] = $group;

        return $group;
    }

    /**
     * Returns the authors of this class comment.
     *
     * @return array(PHP_CodeSniffer_CommentParser_SingleElement)
     */
    public function getAuthors()
    {
        return $this->_authors;
    }

    /**
     * Returns the groups of this class comment.
     *
     * @return array(PHP_CodeSniffer_CommentParser_SingleElement)
     */
    public function getGroups()
    {
        return $this->_groups;
    }
}
