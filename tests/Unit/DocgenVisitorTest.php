<?php

namespace Tests\Unit;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Const_;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use Zerotoprod\DocgenVisitor\DocgenVisitor;

class DocgenVisitorTest extends TestCase
{
    private array $nodeTypeMap = [
        'Class' => Class_::class,
        'Method' => ClassMethod::class,
        'Property' => Property::class,
        'Trait' => Trait_::class,
        'Interface' => Interface_::class,
        'Enum' => Enum_::class,
        'Function' => Function_::class,
        'Constant' => Const_::class,
    ];

    /**
     * @dataProvider provideTestCases
     */
    public function test_docgen_visitor(string $originalCode, string $nodeType, array $comments, string $expectedCode): void
    {
        $parser = (new ParserFactory())->createForHostVersion();
        $stmts = $parser->parse($originalCode);

        $changes = [];
        $traverser = new NodeTraverser();
        $traverser->addVisitor(
            new DocgenVisitor(
                function (Node $node) use ($nodeType, $comments) {
                    if ($node instanceof $this->nodeTypeMap[$nodeType]) {
                        return $comments;
                    }
                    return [];
                },
                $changes
            )
        );

        $traverser->traverse($stmts);

        $updatedCode = $originalCode;
        usort($changes, static fn($a, $b) => $a->start <=> $b->start);

        foreach ($changes as $change) {
            $updatedCode = substr_replace(
                $updatedCode,
                $change->text,
                $change->start,
                $change->end - $change->start + 1
            );
        }

        $this->assertEquals($expectedCode, $updatedCode, "Failed asserting that updated code matches expected for $nodeType");
    }

    public static function provideTestCases(): array
    {
        return [
            // Adding new docblocks
            'Add new docblock to class' => [
                <<<'PHP'
                <?php
                class User {}
                PHP,
                'Class',
                ['New comment'],
                <<<'PHP'
                <?php
                /**
                 * New comment
                 */
                class User {}
                PHP,
            ],
            'Add new docblock to method' => [
                <<<'PHP'
                <?php
                class User {
                    public function getName() {}
                }
                PHP,
                'Method',
                ['Method comment'],
                <<<'PHP'
                <?php
                class User {
                    /**
                     * Method comment
                     */
                    public function getName() {}
                }
                PHP,
            ],
            'Add new docblock to property' => [
                <<<'PHP'
                <?php
                class User {
                    public $name;
                }
                PHP,
                'Property',
                ['Property comment'],
                <<<'PHP'
                <?php
                class User {
                    /**
                     * Property comment
                     */
                    public $name;
                }
                PHP,
            ],
            'Add new docblock to trait' => [
                <<<'PHP'
                <?php
                trait Logger {}
                PHP,
                'Trait',
                ['Trait comment'],
                <<<'PHP'
                <?php
                /**
                 * Trait comment
                 */
                trait Logger {}
                PHP,
            ],
            'Add new docblock to interface' => [
                <<<'PHP'
                <?php
                interface UserInterface {}
                PHP,
                'Interface',
                ['Interface comment'],
                <<<'PHP'
                <?php
                /**
                 * Interface comment
                 */
                interface UserInterface {}
                PHP,
            ],
            'Add new docblock to enum' => [
                <<<'PHP'
                <?php
                enum Status {}
                PHP,
                'Enum',
                ['Enum comment'],
                <<<'PHP'
                <?php
                /**
                 * Enum comment
                 */
                enum Status {}
                PHP,
            ],
            'Add new docblock to function' => [
                <<<'PHP'
                <?php
                function myFunction() {}
                PHP,
                'Function',
                ['Function comment'],
                <<<'PHP'
                <?php
                /**
                 * Function comment
                 */
                function myFunction() {}
                PHP,
            ],
            'Add new docblock to constant' => [
                <<<'PHP'
                <?php
                const MY_CONST = 1;
                PHP,
                'Constant',
                ['Constant comment'],
                <<<'PHP'
                <?php
                /**
                 * Constant comment
                 */
                const MY_CONST = 1;
                PHP,
            ],

            // Updating existing docblocks
            'Update existing docblock on class' => [
                <<<'PHP'
                <?php
                /**
                 * Existing comment
                 */
                class User {}
                PHP,
                'Class',
                ['New comment'],
                <<<'PHP'
                <?php
                /**
                 * Existing comment
                 * New comment
                 */
                class User {}
                PHP,
            ],
            'Update existing docblock on method' => [
                <<<'PHP'
                <?php
                class User {
                    /**
                     * Existing comment
                     */
                    public function getName() {}
                }
                PHP,
                'Method',
                ['New comment'],
                <<<'PHP'
                <?php
                class User {
                    /**
                     * Existing comment
                     * New comment
                     */
                    public function getName() {}
                }
                PHP,
            ],
            'Update single-line docblock on class' => [
                <<<'PHP'
                <?php
                /** Existing comment */
                class User {}
                PHP,
                'Class',
                ['New comment'],
                <<<'PHP'
                <?php
                /**
                 * Existing comment
                 * New comment
                 */
                class User {}
                PHP,
            ],

            // Multiple lines
            'Add multiple lines to class' => [
                <<<'PHP'
                <?php
                class User {}
                PHP,
                'Class',
                ['First line', 'Second line'],
                <<<'PHP'
                <?php
                /**
                 * First line
                 * Second line
                 */
                class User {}
                PHP,
            ],
            'Update with multiple lines on method' => [
                <<<'PHP'
                <?php
                class User {
                    /**
                     * Existing comment
                     */
                    public function getName() {}
                }
                PHP,
                'Method',
                ['First line', 'Second line'],
                <<<'PHP'
                <?php
                class User {
                    /**
                     * Existing comment
                     * First line
                     * Second line
                     */
                    public function getName() {}
                }
                PHP,
            ],

            // No changes scenarios
            'No change when empty comments on class' => [
                <<<'PHP'
                <?php
                class User {}
                PHP,
                'Class',
                [],
                <<<'PHP'
                <?php
                class User {}
                PHP,
            ],
            'No change when empty comments with existing docblock' => [
                <<<'PHP'
                <?php
                /**
                 * Existing comment
                 */
                class User {}
                PHP,
                'Class',
                [],
                <<<'PHP'
                <?php
                /**
                 * Existing comment
                 */
                class User {}
                PHP,
            ],

            // Edge cases
            'Multiple nodes with changes (class)' => [
                <<<'PHP'
                <?php
                class User {
                    public function getName() {}
                }
                PHP,
                'Class',
                ['Class comment'],
                <<<'PHP'
                <?php
                /**
                 * Class comment
                 */
                class User {
                    public function getName() {}
                }
                PHP,
            ],
            'Multiple nodes with method change' => [
                <<<'PHP'
                <?php
                class User {
                    public function getName() {}
                }
                PHP,
                'Method',
                ['Method comment'],
                <<<'PHP'
                <?php
                class User {
                    /**
                     * Method comment
                     */
                    public function getName() {}
                }
                PHP,
            ],
            'Nested structure with property and method' => [
                <<<'PHP'
                <?php
                class User {
                    public $name;
                    public function getName() {}
                }
                PHP,
                'Property',
                ['Property comment'],
                <<<'PHP'
                <?php
                class User {
                    /**
                     * Property comment
                     */
                    public $name;
                    public function getName() {}
                }
                PHP,
            ],
        ];
    }

    /**
     * Test that ensures no unnecessary changes are made when traversing nodes without applicable comments.
     */
    public function test_no_changes_when_callback_always_returns_empty(): void
    {
        $code = <<<'PHP'
        <?php
        class User {
            public $name;
            public function getName() {}
        }
        PHP;

        $changes = [];

        $traverser = new NodeTraverser();
        $traverser->addVisitor(
            new DocgenVisitor(fn() => [], $changes)
        );

        $parser = (new ParserFactory())->createForHostVersion();
        $stmts = $parser->parse($code);
        $traverser->traverse($stmts);

        $this->assertEmpty($changes);
    }
}