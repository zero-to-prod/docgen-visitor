<?php

namespace Tests\Unit;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use Zerotoprod\DocgenVisitor\DocgenVisitor;

class ExampleTest extends TestCase
{
    /** @test */
    public function add_class_comment(): void
    {
        $originalCode = <<<'PHP'
        <?php
        class User 
        {
        
        }
        PHP;

        $parser = (new ParserFactory())->createForHostVersion();
        $stmts = $parser->parse($originalCode);

        $changes = [];
        $comments = ['comment'];
        $traverser = new NodeTraverser();
        $traverser->addVisitor(
            new DocgenVisitor(
                function (Node $node) use ($comments) {
                    // Only add a docblock for the Class_ node
                    if ($node instanceof Node\Stmt\Class_) {
                        return $comments;
                    }

                    return [];
                },
                $changes
            )
        );

        $traverser->traverse($stmts);

        $this->assertNotEmpty($changes, 'No docblock changes were generated.');

        $updatedCode = $originalCode;
        foreach ($changes as $change) {
            $updatedCode = substr_replace(
                $updatedCode,
                $change->text,
                $change->start,
                $change->end - $change->start + 1
            );
        }

        $expectedCode = <<<PHP
        <?php
        /**
         * comment
         */
        class User 
        {
        
        }
        PHP;

        $this->assertEquals($expectedCode, $updatedCode);
    }

    /** @test */
    public function updates_class_comment(): void
    {
        $originalCode = <<<'PHP'
        <?php
        /**
         * existing
         */
        class User
        {
            public function method(): string
            {
                return '';
            }
        }
        PHP;

        $parser = (new ParserFactory())->createForHostVersion();
        $stmts = $parser->parse($originalCode);

        $changes = [];
        $comments = ['comment'];
        $traverser = new NodeTraverser();
        $traverser->addVisitor(
            new DocgenVisitor(
                function (Node $node) use ($comments) {
                    // Only add a docblock for the Class_ node
                    if ($node instanceof Node\Stmt\Class_) {
                        return $comments;
                    }

                    return [];
                },
                $changes
            )
        );

        $traverser->traverse($stmts);

        $this->assertNotEmpty($changes, 'No docblock changes were generated.');

        $updatedCode = $originalCode;
        foreach ($changes as $change) {
            $updatedCode = substr_replace(
                $updatedCode,
                $change->text,
                $change->start,
                $change->end - $change->start + 1
            );
        }

        $expectedCode = <<<PHP
        <?php
        /**
         * existing
         * comment
         */
        class User
        {
            public function method(): string
            {
                return '';
            }
        }
        PHP;

        $this->assertEquals($expectedCode, $updatedCode);
    }

}