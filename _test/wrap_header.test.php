<?php
/**
 * Test if headers inside the wrap syntax are correctly processed
 */
class plugin_wrap_header_test extends DokuWikiTest {
    public function setUp() {
        $this->pluginsEnabled[] = 'wrap';
        parent::setUp();
    }

    public function test_instructions() {
        $instructions = p_get_instructions("<WRAP>\n==== Heading ====\n\nSome text\n</WRAP>");
        $expected =
            array(
                array(
                    'document_start',
                    array(),
                    0
                ),
                array(
                    'plugin',
                    array(
                        'wrap_div',
                        array(
                            DOKU_LEXER_ENTER,
                            '<wrap'
                        ),
                        DOKU_LEXER_ENTER,
                        '<WRAP>'
                    ),
                    1
                ),
                array(
                    'header',
                    array(
                        'Heading',
                        3,
                        8
                    ),
                    8
                ),
                array(
                    'plugin',
                    array(
                        'wrap_closesection',
                        array(),
                        DOKU_LEXER_SPECIAL,
                        false
                    ),
                    8
                ),
                array(
                    'p_open',
                    array(),
                    8
                ),
                array(
                    'cdata',
                    array(
                        'Some text'
                    ),
                    27
                ),
                array(
                    'p_close',
                    array(),
                    37
                ),
                array(
                    'plugin',
                    array(
                        'wrap_div',
                        array(
                            DOKU_LEXER_EXIT,
                            ''
                        ),
                        DOKU_LEXER_EXIT,
                        '</WRAP>'
                    ),
                    37
                ),
                array(
                    'document_end',
                    array(),
                    37
                )
            );
        $this->assertEquals($expected, $instructions);
    }
}