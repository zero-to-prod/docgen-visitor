<?php

namespace Zerotoprod\DocgenVisitor;

use Closure;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * A PHP AST visitor for automatically generating or updating docblocks in PHP source code.
 *
 * @link https://github.com/zero-to-prod/docgen-visitor
 */
class DocgenVisitor extends NodeVisitorAbstract
{
    private readonly Closure $callback;
    private array $changes;
    private readonly array $top_level_declarations;

    /**
     * Constructor for DocgenVisitor.
     *
     * @param  Closure  $callback                A callback function that determines the comment lines for each node.
     * @param  array    $changes                 Reference to an array where changes will be stored.
     * @param  array    $top_level_declarations  Determines indentation level for things like classes and traits.
     *
     * @link https://github.com/zero-to-prod/docgen-visitor
     */
    public function __construct(
        Closure $callback,
        array &$changes,
        array $top_level_declarations = [
            Node\Stmt\Class_::class,
            Node\Stmt\Enum_::class,
            Node\Stmt\Interface_::class,
            Node\Stmt\Trait_::class,
        ]
    ) {
        $this->callback = $callback;
        $this->changes = &$changes;
        $this->top_level_declarations = $top_level_declarations;
    }

    /**
     * Processes each node encountered during the AST traversal.
     *
     * This method checks if there are comments to add or update for the current node.
     * If comments exist, it updates them; otherwise, it adds new comments.
     *
     * @param  Node  $node  The PHP Parser node being visited.
     *
     * @link https://github.com/zero-to-prod/docgen-visitor
     */
    public function enterNode(Node $node): void
    {
        $lines = ($this->callback)($node);

        if (!is_array($lines) || empty($lines)) {
            return;
        }

        $text = $node->getDocComment()?->getText();

        $this->changes[] = Change::from([
            Change::start => $node->getStartFilePos(),
            Change::end => $node->getEndFilePos() - ($text ? 0 : 1),
            Change::text => $this->render(
                $text,
                $lines,
                !in_array($node::class, $this->top_level_declarations, true)
            ),
        ]);
    }

    private function render(?string $text, array $lines, bool $indent): string
    {
        $asterisk = $indent ? '     * ' : ' * ';

        if ($text) {
            $base = str_contains($text, "\n")
                ? rtrim($text, " */\n")
                : '/**'."\n".$asterisk.trim(substr($text, 3, -2));
        } else {
            $base = "/**";
        }

        foreach ($lines as $line) {
            $base .= "\n$asterisk$line";
        }

        $closing = $indent ? '     */' : ' */';
        $padding = $indent ? "\n    " : "\n";

        return $base."\n$closing".($text ? '' : $padding);
    }
}