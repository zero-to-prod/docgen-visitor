<?php
declare(strict_types=1);

namespace Zerotoprod\DocgenVisitor;

use Closure;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Const_;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;

/**
 * A PHP AST visitor for automatically generating or updating docblocks in PHP source code.
 *
 * @link https://github.com/zero-to-prod/docgen-visitor
 */
class DocgenVisitor extends \PhpParser\NodeVisitorAbstract
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

        $Doc = $node->getDocComment();
        $start = $node->getStartFilePos();

        $this->changes[] = Change::from([
            Change::start => $Doc ? $Doc->getStartFilePos() : $start,
            Change::end => $Doc ? $Doc->getEndFilePos() : $start - 1,
            Change::text => $this->render(
                $Doc?->getText(),
                $lines,
                !in_array($node::class, [
                    Class_::class,
                    Enum_::class,
                    Interface_::class,
                    Trait_::class,
                    Function_::class,
                    Const_::class,
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
                : "/**\n$asterisk".trim(substr($text, 3, -3));
            $base .= "\n".implode("\n", array_map(static fn($line) => $asterisk.$line, $lines));

            return "$base\n$closing";
        }

        $content = implode("\n", array_map(static fn($line) => $asterisk.$line, $lines));
        $padding = $indent ? "\n    " : "\n";

        return "/**\n$content\n$closing$padding";
    }
}