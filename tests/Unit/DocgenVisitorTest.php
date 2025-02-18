<?php

namespace Tests\Unit;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use Zerotoprod\DocgenVisitor\DocgenVisitor;

class DocgenVisitorTest extends TestCase
{
    /** @test */
    public function it_adds_a_new_docblock_to_a_class(): void
    {
        $code = "<?php\nclass User {}";
        $changes = [];

        $traverser = new NodeTraverser();
        $traverser->addVisitor(
            new DocgenVisitor(function (Node $node) {
                return $node instanceof Class_ ? ['New comment'] : [];
            }, $changes)
        );

        $parser = (new ParserFactory())->createForHostVersion();
        $stmts = $parser->parse($code);
        $traverser->traverse($stmts);

        $this->assertCount(1, $changes);
        $this->assertEquals("/**\n * New comment\n */\n", $changes[0]->text);
    }

    /** @test */
    public function it_updates_an_existing_docblock(): void
    {
        $code = "<?php\n/** Existing */\nclass User {}";
        $changes = [];

        $traverser = new NodeTraverser();
        $traverser->addVisitor(
            new DocgenVisitor(function (Node $node) {
                return $node instanceof Class_ ? ['Additional comment'] : [];
            }, $changes)
        );

        $parser = (new ParserFactory())->createForHostVersion();
        $stmts = $parser->parse($code);
        $traverser->traverse($stmts);

        $this->assertCount(1, $changes);
        $this->assertStringContainsString('Existing', $changes[0]->text);
        $this->assertStringContainsString('Additional comment', $changes[0]->text);
    }

    /** @test */
    public function it_does_not_add_a_docblock_when_no_changes_are_returned(): void
    {
        $code = "<?php\nclass User {}";
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

    /** @test */
    public function it_adds_a_docblock_to_a_property(): void
    {
        $code = "<?php\nclass User { public \$name; }";
        $changes = [];

        $traverser = new NodeTraverser();
        $traverser->addVisitor(
            new DocgenVisitor(fn(Node $node) => $node instanceof Property ? ['Property comment'] : [], $changes)
        );

        $parser = (new ParserFactory())->createForHostVersion();
        $stmts = $parser->parse($code);
        $traverser->traverse($stmts);

        $this->assertCount(1, $changes);
        $this->assertStringContainsString('Property comment', $changes[0]->text);
    }

    /** @test */
    public function it_adds_a_docblock_to_a_method(): void
    {
        $code = "<?php\nclass User { public function getName() {} }";
        $changes = [];

        $traverser = new NodeTraverser();
        $traverser->addVisitor(
            new DocgenVisitor(fn(Node $node) => $node instanceof ClassMethod ? ['Method comment'] : [], $changes)
        );

        $parser = (new ParserFactory())->createForHostVersion();
        $stmts = $parser->parse($code);
        $traverser->traverse($stmts);

        $this->assertCount(1, $changes);
        $this->assertStringContainsString('Method comment', $changes[0]->text);
    }

    /** @test */
    public function it_handles_multiple_lines_in_comments(): void
    {
        $code = "<?php\nclass User {}";
        $changes = [];

        $traverser = new NodeTraverser();
        $traverser->addVisitor(
            new DocgenVisitor(function (Node $node) {
                return $node instanceof Class_ ? ["First line", "Second line"] : [];
            }, $changes)
        );

        $parser = (new ParserFactory())->createForHostVersion();
        $stmts = $parser->parse($code);
        $traverser->traverse($stmts);

        $this->assertCount(1, $changes);
        $this->assertEquals("/**\n * First line\n * Second line\n */\n", $changes[0]->text);
    }

    /** @test */
    public function it_updates_existing_multi_line_docblock(): void
    {
        $code = "<?php\n/**\n * Existing comment\n */\nclass User {}";
        $changes = [];

        $traverser = new NodeTraverser();
        $traverser->addVisitor(
            new DocgenVisitor(function (Node $node) {
                return $node instanceof Class_ ? ['New comment'] : [];
            }, $changes)
        );

        $parser = (new ParserFactory())->createForHostVersion();
        $stmts = $parser->parse($code);
        $traverser->traverse($stmts);

        $this->assertCount(1, $changes);
        $this->assertStringContainsString('Existing comment', $changes[0]->text);
        $this->assertStringContainsString('New comment', $changes[0]->text);
    }

    /** @test */
    public function it_does_nothing_when_no_lines_are_provided(): void
    {
        $code = "<?php\nclass User {}";
        $changes = [];

        $traverser = new NodeTraverser();
        $traverser->addVisitor(
            new DocgenVisitor(function () {
                return [];
            }, $changes)
        );

        $parser = (new ParserFactory())->createForHostVersion();
        $stmts = $parser->parse($code);
        $traverser->traverse($stmts);

        $this->assertEmpty($changes);
    }

    /** @test */
    public function it_adds_a_docblock_to_a_trait(): void
    {
        $code = "<?php\ntrait Logger {}";
        $changes = [];

        $traverser = new NodeTraverser();
        $traverser->addVisitor(
            new DocgenVisitor(fn(Node $node) => $node instanceof Trait_ ? ['Trait comment'] : [], $changes)
        );

        $parser = (new ParserFactory())->createForHostVersion();
        $stmts = $parser->parse($code);
        $traverser->traverse($stmts);

        $this->assertCount(1, $changes);
        $this->assertStringContainsString('Trait comment', $changes[0]->text);
    }

    /** @test */
    public function it_updates_an_existing_docblock_for_a_trait(): void
    {
        $code = "<?php\n/** Existing doc */\ntrait Logger {}";
        $changes = [];

        $traverser = new NodeTraverser();
        $traverser->addVisitor(
            new DocgenVisitor(fn(Node $node) => $node instanceof Trait_ ? ['Updated comment'] : [], $changes)
        );

        $parser = (new ParserFactory())->createForHostVersion();
        $stmts = $parser->parse($code);
        $traverser->traverse($stmts);

        $this->assertCount(1, $changes);
        $this->assertStringContainsString('Existing doc', $changes[0]->text);
        $this->assertStringContainsString('Updated comment', $changes[0]->text);
    }
    /** @test */
    public function it_does_nothing_when_no_docblock_changes_are_returned_for_a_trait(): void
    {
        $code = "<?php\ntrait Logger {}";
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
