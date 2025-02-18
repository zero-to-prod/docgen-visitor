<?php

namespace Zerotoprod\DocgenVisitor;

use Closure;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class DocgenVisitor extends NodeVisitorAbstract
{
    private Closure $callback;
    private array $changes;

    public function __construct(Closure $callback, array &$changes)
    {
        $this->callback = $callback;
        $this->changes = &$changes;
    }

    public function enterNode(Node $node): void
    {
        $lines = ($this->callback)($node);

        if (!is_array($lines) || empty($lines)) {
            return;
        }

        $comment = $node->getDocComment();
        $start = $node->getStartFilePos();

        if ($comment) {
            $this->changes[] = $this->createChange(
                $comment->getStartFilePos(),
                $comment->getEndFilePos(),
                $this->format($comment->getText(), $lines, $this->shouldIndent($node))
            );
        } else {
            $this->changes[] = $this->createChange(
                $start,
                $start - 1,
                $this->render($lines, $node)
            );
        }
    }

    private function shouldIndent(Node $node): bool
    {
        return !($node instanceof Node\Stmt\Class_ || $node instanceof Node\Stmt\Enum_ || $node instanceof Node\Stmt\Interface_);
    }

    private function render(array $lines, Node $Node): string
    {
        $indent = $this->shouldIndent($Node);
        $asterisk = $indent ? '     * ' : ' * ';
        $closing = $indent ? '     */' : ' */';
        $padding = $indent ? "\n    " : "\n";

        $doc = "/**\n";

        foreach ($lines as $line) {
            $doc .= "$asterisk$line\n";
        }

        return $doc.$closing.$padding;
    }

    private function format($text, $lines, $indent): string
    {
        $asterisk = $indent ? '     * ' : ' * ';
        $closing = $indent ? '     */' : ' */';

        $base = str_contains($text, "\n")
            ? rtrim($text, " */\n")
            : '/**'."\n".$asterisk.trim(substr($text, 3, -2));

        foreach ($lines as $line) {
            $base .= "\n$asterisk$line";
        }

        return $base."\n$closing";
    }

    private function createChange($start, $end, $text): Change
    {
        return Change::from([
            Change::start => $start,
            Change::end => $end,
            Change::text => $text,
        ]);
    }
}