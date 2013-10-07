<?php
/**
 * Parses and verifies the doc comments for classes.
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

require_once __DIR__ . '/../../CommentParser/ClassCommentParser.php';

/**
 * Parses and verifies the doc comments for classes.
 *
 * Verifies that :
 * <ul>
 *  <li>A doc comment exists.</li>
 *  <li>There is at least one @author tag.</li>
 *  <li>Check the authors are known.</li>
 *  <li>Check that tests have either @group Unit or Functional.</li>
 * </ul>
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
class Instaclick_Sniffs_Commenting_ClassCommentSniff implements PHP_CodeSniffer_Sniff
{
    /**
     * The header comment parser for the current file.
     *
     * @var PHP_CodeSniffer_Comment_Parser_ClassCommentParser
     */
    protected $commentParser = null;

    /**
     * The current PHP_CodeSniffer_File object we are processing.
     *
     * @var PHP_CodeSniffer_File
     */
    protected $currentFile = null;

    /**
     * Recognized list of authors.
     *
     * @var array
     */
    protected $authors = null;

    /**
     * Tags in correct order and related info.
     *
     * @var array
     */
    protected $tags = array(
        'group' => array(
            'required'       => false,
            'allow_multiple' => true,
            'order_text'     => 'precedes @author',
        ),
        'author'     => array(
            'required'       => true,
            'allow_multiple' => true,
            'order_text'     => 'follows @group (if used)',
        ),
    );

    /**
     * Constructor
     */
    public function __construct()
    {
        $directory  = isset($_SERVER['argv'][0]) ? $_SERVER['argv'][0] : getcwd();
        $authorFile = dirname($directory) . '/AUTHORS.txt';

        $this->authors = file_exists($authorFile) ? preg_grep('~^(#|\s*$)~', file($authorFile, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES), PREG_GREP_INVERT) : array();
    }

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(
            T_CLASS,
            T_INTERFACE,
        );
    }

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
        $this->currentFile = $phpcsFile;

        $tokens    = $phpcsFile->getTokens();
        $type      = strtolower($tokens[$stackPtr]['content']);
        $errorData = array($type);
        $find      = array(
                      T_ABSTRACT,
                      T_WHITESPACE,
                      T_FINAL,
                     );

        // Extract the class comment docblock.
        $commentEnd = $phpcsFile->findPrevious($find, ($stackPtr - 1), null, true);

        if ($commentEnd !== false && $tokens[$commentEnd]['code'] === T_COMMENT) {
            $error = 'You must use "/**" style comments for a %s comment';
            $phpcsFile->addError($error, $stackPtr, 'WrongStyle', $errorData);
            return;
        } else if ($commentEnd === false
            || $tokens[$commentEnd]['code'] !== T_DOC_COMMENT
        ) {
            $phpcsFile->addError('Missing %s doc comment', $stackPtr, 'Missing', $errorData);
            return;
        }

        $commentStart = ($phpcsFile->findPrevious(T_DOC_COMMENT, ($commentEnd - 1), null, true) + 1);
        $commentNext  = $phpcsFile->findPrevious(T_WHITESPACE, ($commentEnd + 1), $stackPtr, false, $phpcsFile->eolChar);

        // Distinguish file and class comment.
        $prevClassToken = $phpcsFile->findPrevious(T_CLASS, ($stackPtr - 1));
        if ($prevClassToken === false) {
            // This is the first class token in this file, need extra checks.
            $prevNonComment = $phpcsFile->findPrevious(T_DOC_COMMENT, ($commentStart - 1), null, true);
            if ($prevNonComment !== false) {
                $prevComment = $phpcsFile->findPrevious(T_DOC_COMMENT, ($prevNonComment - 1));
                if ($prevComment === false) {
                    // There is only 1 doc comment between open tag and class token.
                    $newlineToken = $phpcsFile->findNext(T_WHITESPACE, ($commentEnd + 1), $stackPtr, false, $phpcsFile->eolChar);
                    if ($newlineToken !== false) {
                        $newlineToken = $phpcsFile->findNext(
                            T_WHITESPACE,
                            ($newlineToken + 1),
                            $stackPtr,
                            false,
                            $phpcsFile->eolChar
                        );

                        if ($newlineToken !== false) {
                            // Blank line between the class and the doc block.
                            // The doc block is most likely a file comment.
                            $error = 'Missing %s doc comment';
                            $phpcsFile->addError($error, ($stackPtr + 1), 'Missing', $errorData);
                            return;
                        }
                    }
                }
            }
        }

        $comment = $phpcsFile->getTokensAsString(
            $commentStart,
            ($commentEnd - $commentStart + 1)
        );

        // Parse the class comment.docblock.
        try {
            $this->commentParser = new Instaclick_CodeSniffer_CommentParser_ClassCommentParser($comment, $phpcsFile);
            $this->commentParser->parse();
        } catch (PHP_CodeSniffer_CommentParser_ParserException $e) {
            $line = ($e->getLineWithinComment() + $commentStart);
            $phpcsFile->addError($e->getMessage(), $line, 'FailedParse');
            return;
        }

        $comment = $this->commentParser->getComment();
        if (is_null($comment) === true) {
            $error = 'Doc comment is empty for %s';
            $phpcsFile->addError($error, $commentStart, 'Empty', $errorData);
            return;
        }

        // No extra newline before short description.
        $short        = $comment->getShortComment();
        $newlineCount = 0;
        $newlineSpan  = strspn($short, $phpcsFile->eolChar);
        if ($short !== '' && $newlineSpan > 0) {
            $error = 'Extra newline(s) found before %s comment short description';
            $phpcsFile->addError($error, ($commentStart + 1), 'SpacingBeforeShort', $errorData);
        }

        $newlineCount = (substr_count($short, $phpcsFile->eolChar) + 1);

        // Exactly one blank line between short and long description.
        $long = $comment->getLongComment();
        if (empty($long) === false) {
            $between        = $comment->getWhiteSpaceBetween();
            $newlineBetween = substr_count($between, $phpcsFile->eolChar);
            if ($newlineBetween !== 2) {
                $error = 'There must be exactly one blank line between descriptions in %s comments';
                $phpcsFile->addError($error, ($commentStart + $newlineCount + 1), 'SpacingAfterShort', $errorData);
            }

            $newlineCount += $newlineBetween;
        }

        // Exactly one blank line before tags.
        $tags = $this->commentParser->getTagOrders();
        if (count($tags) > 1) {
            $newlineSpan = $comment->getNewlineAfter();
            if ($newlineSpan !== 2) {
                $error = 'There must be exactly one blank line before the tags in %s comments';
                if ($long !== '') {
                    $newlineCount += (substr_count($long, $phpcsFile->eolChar) - $newlineSpan + 1);
                }

                $phpcsFile->addError($error, ($commentStart + $newlineCount), 'SpacingBeforeTags', $errorData);
                $short = rtrim($short, $phpcsFile->eolChar.' ');
            }
        }

        // Check each tag.
        $this->processTags($commentStart, $commentEnd);

    }

    /**
     * Processes each required or optional tag.
     *
     * @param int $commentStart Position in the stack where the comment started.
     * @param int $commentEnd   Position in the stack where the comment ended.
     *
     * @return void
     */
    protected function processTags($commentStart, $commentEnd)
    {
        $docBlock    = (get_class($this) === 'PEAR_Sniffs_Commenting_FileCommentSniff') ? 'file' : 'class';
        $foundTags   = $this->commentParser->getTagOrders();
        $orderIndex  = 0;
        $indentation = array();
        $longestTag  = 0;
        $errorPos    = 0;

        foreach ($this->tags as $tag => $info) {

            // Required tag missing.
            if ($info['required'] === true && in_array($tag, $foundTags) === false) {
                $error = 'Missing @%s tag in %s comment';
                $data  = array(
                          $tag,
                          $docBlock,
                         );
                $this->currentFile->addError($error, $commentEnd, 'MissingTag', $data);
                continue;
            }

             // Get the line number for current tag.
            $tagName = ucfirst($tag);
            if ($info['allow_multiple'] === true) {
                $tagName .= 's';
            }

            $getMethod  = 'get'.$tagName;
            $tagElement = $this->commentParser->$getMethod();
            if (is_null($tagElement) === true || empty($tagElement) === true) {
                continue;
            }

            $errorPos = $commentStart;
            if (is_array($tagElement) === false) {
                $errorPos = ($commentStart + $tagElement->getLine());
            }

            // Get the tag order.
            $foundIndexes = array_keys($foundTags, $tag);

            if (count($foundIndexes) > 1) {
                // Multiple occurrence not allowed.
                if ($info['allow_multiple'] === false) {
                    $error = 'Only 1 @%s tag is allowed in a %s comment';
                    $data  = array(
                              $tag,
                              $docBlock,
                             );
                    $this->currentFile->addError($error, $errorPos, 'DuplicateTag', $data);
                } else {
                    // Make sure same tags are grouped together.
                    $i     = 0;
                    $count = $foundIndexes[0];
                    foreach ($foundIndexes as $index) {
                        if ($index !== $count) {
                            $errorPosIndex
                                = ($errorPos + $tagElement[$i]->getLine());
                            $error = '@%s tags must be grouped together';
                            $data  = array($tag);
                            $this->currentFile->addError($error, $errorPosIndex, 'TagsNotGrouped', $data);
                        }

                        $i++;
                        $count++;
                    }
                }
            }

            // Check tag order.
            if ($foundIndexes[0] > $orderIndex) {
                $orderIndex = $foundIndexes[0];
            } else {
                if (is_array($tagElement) === true && empty($tagElement) === false) {
                    $errorPos += $tagElement[0]->getLine();
                }

                $error = 'The @%s tag is in the wrong order; the tag %s';
                $data  = array(
                          $tag,
                          $info['order_text'],
                         );
                $this->currentFile->addError($error, $errorPos, 'WrongTagOrder', $data);
            }

            // Store the indentation for checking.
            $len = strlen($tag);
            if ($len > $longestTag) {
                $longestTag = $len;
            }

            if (is_array($tagElement) === true) {
                foreach ($tagElement as $key => $element) {
                    $indentation[] = array(
                                      'tag'   => $tag,
                                      'line'  => $element->getLine(),
                                     );
                }
            } else {
                $indentation[] = array(
                                  'tag'   => $tag,
                                 );
            }

            $method = 'process'.$tagName;
            if (method_exists($this, $method) === true) {
                // Process each tag if a method is defined.
                call_user_func(array($this, $method), $errorPos);
            } else {
                if (is_array($tagElement) === true) {
                    foreach ($tagElement as $key => $element) {
                        $element->process(
                            $this->currentFile,
                            $commentStart,
                            $docBlock
                        );
                    }
                } else {
                     $tagElement->process(
                         $this->currentFile,
                         $commentStart,
                         $docBlock
                     );
                }
            }
        }
    }

    /**
     * Process the author tag(s) that this header comment has.
     *
     * This function is different from other _process functions
     * as $authors is an array of SingleElements, so we work out
     * the errorPos for each element separately
     *
     * @param int $commentStart The position in the stack where
     *                          the comment started.
     *
     * @return void
     */
    protected function processAuthors($commentStart)
    {
        $authors = $this->commentParser->getAuthors();

        if (empty($authors)) {
            return;
        }

        foreach ($authors as $author) {
            $errorPos = ($commentStart + $author->getLine());
            $content  = $author->getContent();

            if ($content !== '') {
                $local = '\da-zA-Z-_+';

                // Dot character cannot be the first or last character
                // in the local-part.
                $localMiddle = $local.'.\w';

                if (preg_match('/^([^<]*)\s+<(['.$local.'](['.$localMiddle.']*['.$local.'])*@[\da-zA-Z][-.\w]*[\da-zA-Z]\.[a-zA-Z]{2,7})>$/', $content) === 0) {
                    $error = 'Content of the @author tag must be in the form "Display Name <username@example.com>"';
                    $this->currentFile->addError($error, $errorPos, 'InvalidAuthors');
                } elseif (count($this->authors) && ! in_array($content, $this->authors)) {
                    $error = '@author "%s" not found in "AUTHORS.txt"';
                    $this->currentFile->addError(sprintf($error, $content), $errorPos, 'UnknownAuthors');
                }
            } else {
                $error    = 'Content missing for @author tag in %s comment';
                $docBlock = 'class';
                $data     = array($docBlock);

                $this->currentFile->addError($error, $errorPos, 'EmptyAuthors', $data);
            }
        }
    }

    /**
     * Process the group tag.
     *
     * @param int $errorPos The line number where the error occurs.
     *
     * @return void
     */
    protected function processGroups($errorPos)
    {
        if ( ! preg_match('~.+Test[.]php$~', $this->currentFile->getFilename())) {
            return;
        }

        $groups = $this->commentParser->getGroups();

        if ( ! $groups) {
            return;
        }

        $found = false;

        foreach ($groups as $group) {
            $content = $group->getContent();

            if (in_array($content, array('Unit', 'Functional'))) {
                $found = true;
            }
        }

        if ( ! $found) {
            $error = '@group tag must contain either Unit or Functional';
            $this->currentFile->addError($error, $errorPos, 'EmptyGroup');
        }
    }
}
