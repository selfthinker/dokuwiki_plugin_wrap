<?php

namespace dokuwiki\plugin\wrap\test;

use DokuWikiTest;

/**
 * Tests to ensure wrap syntax is correctly processed
 *
 * @group plugin_wrap
 * @group plugins
 */
class SyntaxTest extends DokuWikiTest {

    protected $pluginsEnabled = ['wrap'];

    public function testNestedHeading() {
        $instructions = p_get_instructions("<WRAP>\n==== Heading ====\n\nSome text\n</WRAP>");
        $expected =
            [
                [
                    'document_start',
                    [],
                    0
                ],
                [
                    'plugin',
                    [
                        'wrap_divwrap',
                        [
                            DOKU_LEXER_ENTER,
                            '<wrap'
                        ],
                        DOKU_LEXER_ENTER,
                        '<WRAP>'
                    ],
                    1
                ],
                [
                    'header',
                    [
                        'Heading',
                        3,
                        8
                    ],
                    8
                ],
                [
                    'plugin',
                    [
                        'wrap_closesection',
                        [],
                        DOKU_LEXER_SPECIAL,
                        false
                    ],
                    8
                ],
                [
                    'p_open',
                    [],
                    8
                ],
                [
                    'cdata',
                    [
                        'Some text'
                    ],
                    27
                ],
                [
                    'p_close',
                    [],
                    37
                ],
                [
                    'plugin',
                    [
                        'wrap_divwrap',
                        [
                            DOKU_LEXER_EXIT,
                            ''
                        ],
                        DOKU_LEXER_EXIT,
                        '</WRAP>'
                    ],
                    37
                ],
                [
                    'document_end',
                    [],
                    37
                ]
            ];
        $this->assertEquals($expected, $instructions);
    }

    public function testBlockNesting() {
        $instructions = p_get_instructions("<WRAP>\nFoo\n\n</div> </block> Bar\n</WRAP>");
        $expected =
            [
                [
                    'document_start',
                    [],
                    0
                ],
                [
                    'plugin',
                    [
                        'wrap_divwrap',
                        [
                            DOKU_LEXER_ENTER,
                            '<wrap'
                        ],
                        DOKU_LEXER_ENTER,
                        '<WRAP>'
                    ],
                    1
                ],
                [
                    'p_open',
                    [
                    ],
                    1
                ],
                [
                    'cdata',
                    [
                        'Foo'
                    ],
                    8
                ],
                [
                    'p_close',
                    [],
                    11
                ],
                [
                    'p_open',
                    [
                    ],
                    11
                ],
                [
                    'cdata',
                    [
                        '</div> </block> Bar'
                    ],
                    13
                ],
                [
                    'p_close',
                    [],
                    33
                ],
                [
                    'plugin',
                    [
                        'wrap_divwrap',
                        [
                            DOKU_LEXER_EXIT,
                            ''
                        ],
                        DOKU_LEXER_EXIT,
                        '</WRAP>'
                    ],
                    33
                ],
                [
                    'document_end',
                    [],
                    33
                ]
            ];
        $this->assertEquals($expected, $instructions);
    }

    public function testInlineNesting() {
        $instructions = p_get_instructions("<wrap>Foo </span> </inline> Bar</wrap>");
        $expected =
            [
                [
                    'document_start',
                    [],
                    0
                ],
                [
                    'p_open',
                    [
                    ],
                    0
                ],
                [
                    'plugin',
                    [
                        'wrap_spanwrap',
                        [
                            DOKU_LEXER_ENTER,
                            '<wrap'
                        ],
                        DOKU_LEXER_ENTER,
                        '<wrap>'
                    ],
                    1
                ],
                [
                    'cdata',
                    [
                        'Foo </span> </inline> Bar'
                    ],
                    7
                ],
                [
                    'plugin',
                    [
                        'wrap_spanwrap',
                        [
                            DOKU_LEXER_EXIT,
                            ''
                        ],
                        DOKU_LEXER_EXIT,
                        '</wrap>'
                    ],
                    32
                ],
                [
                    'cdata',
                    [
                        ''
                    ],
                    39
                ],
                [
                    'p_close',
                    [],
                    39
                ],
                [
                    'document_end',
                    [],
                    39
                ]
            ];
        $this->assertEquals($expected, $instructions);
    }

}
