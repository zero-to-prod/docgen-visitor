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
    /**
     * Constructor for DocgenVisitor.
     *
     * @param  Closure  $callback  A callback function that determines the comment lines for each node.
     * @param  array    $changes   Reference to an array where changes will be stored.
     *
     * @link https://github.com/zero-to-prod/docgen-visitor
     */
    public function __construct(private readonly Closure $callback, private array &$changes)
    {
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

        if (empty($lines)) {
            return;
        }

        $comment = $node->getDocComment();
        $start = $node->getStartFilePos();

        $this->changes[] = Change::from([
            Change::start => $comment ? $comment->getStartFilePos() : $start,
            Change::end => $comment ? $comment->getEndFilePos() : $start - 1,
            Change::text => $this->render(
                $comment?->getText(),
                $lines,
                !in_array($node::class, [
                    Node\Stmt\Class_::class,
                    Node\Stmt\Enum_::class,
                    Node\Stmt\Interface_::class,
                    Node\Stmt\Trait_::class,
                ], true)
            ),
        ]);
    }

    private function render(?string $text, array $lines, bool $indent): string
    {
        $asterisk = $indent ? '     * ' : ' * ';
        $closing = $indent ? '     */' : ' */';

        if ($text) {
            $base = str_contains($text, "\n")
                ? rtrim($text, " */\n")
                : "/**\n$asterisk".trim(substr($text, 3, -2));

            $base .= "\n".implode("\n", array_map(static fn($line) => $asterisk.$line, $lines));

            return "$base\n$closing";
        }

        $content = implode("\n", array_map(static fn($line) => $asterisk.$line, $lines));
        $padding = $indent ? "\n    " : "\n";

        return "/**\n$content\n$closing$padding";
    }
}