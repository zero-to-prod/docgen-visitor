<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use Zerotoprod\DocgenVisitor\DocgenVisitor;

class ExampleTest extends TestCase
{
    public function testItAddsOrUpdatesDocblockForAClass(): void
    {
        // --- 1. The original code we're testing against ---
        $originalCode = <<<'PHP'
<?php
class User {}
PHP;

        // --- 2. Parse the original code into an AST ---
        $parser = (new ParserFactory())->createForHostVersion();
        $stmts = $parser->parse($originalCode);

        // --- 3. Traverse the AST & determine docblock changes ---
        $changes = [];
        $comments = ['This is an updated class docblock'];
        $traverser = new NodeTraverser();
        $traverser->addVisitor(
            new DocgenVisitor(
            // Callback: return doc lines for class nodes
                function (Node $node) use ($comments) {
                    if ($node instanceof Node\Stmt\Class_) {
                        return $comments;
                    }
                    return [];
                },
                $changes
            )
        );

        $traverser->traverse($stmts);

        // We expect at least one change if we had a class with no docblock
        $this->assertNotEmpty($changes, 'No docblock changes were generated.');

        // --- 4. Apply changes to the original code (in-memory) ---
        $updatedCode = $originalCode;
        foreach ($changes as $change) {
            $updatedCode = substr_replace(
                $updatedCode,
                $change->text,
                $change->start,
                $change->end - $change->start + 1
            );
        }

        // --- 5. Verify the updated docblock ---
        $this->assertStringContainsString('This is an updated class docblock', $updatedCode);
        $this->assertStringContainsString('/**', $updatedCode);
        $this->assertStringContainsString('*/', $updatedCode);
    }
}