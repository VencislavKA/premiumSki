<?php

/**
 * SCSSPHP
 *
 * @copyright 2012-2020 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */

namespace ScssPhpRBE\ScssPhp\Parser;

use ScssPhpRBE\ScssPhp\Ast\Sass\Interpolation;
use ScssPhpRBE\ScssPhp\Ast\Sass\Statement\LoudComment;
use ScssPhpRBE\ScssPhp\Ast\Sass\Statement\SilentComment;
use ScssPhpRBE\ScssPhp\Util\Character;

/**
 * A parser for the CSS-compatible syntax.
 *
 * @internal
 */
class ScssParser extends StylesheetParser
{
    protected function isIndented(): bool
    {
        return false;
    }

    protected function getCurrentIndentation(): int
    {
        return 0;
    }

    protected function styleRuleSelector(): Interpolation
    {
        return $this->almostAnyValue();
    }

    protected function expectStatementSeparator(?string $name = null): void
    {
        $this->whitespaceWithoutComments();

        if ($this->scanner->isDone()) {
            return;
        }

        $next = $this->scanner->peekChar();

        if ($next === ';' || $next === '}') {
            return;
        }

        $this->scanner->expectChar(';');
    }

    protected function atEndOfStatement(): bool
    {
        $next = $this->scanner->peekChar();

        return $next === null || $next === ';' || $next === '}' || $next === '{';
    }

    protected function lookingAtChildren(): bool
    {
        return $this->scanner->peekChar() === '{';
    }

    protected function scanElse(int $ifIndentation): bool
    {
        $start = $this->scanner->getPosition();
        $this->whitespace();
        $beforeAt = $this->scanner->getPosition();

        if ($this->scanner->scanChar('@')) {
            if ($this->scanIdentifier('else', true)) {
                return true;
            }

            if ($this->scanIdentifier('elseif', true)) {
                $span = $this->scanner->spanFrom($beforeAt);
                $line = $span->getStart()->getLine() + 1;
                $column = $span->getStart()->getColumn() + 1;
                $sourceUrl = $this->sourceUrl;

                $message = "@elseif is deprecated and will not be supported in future Sass versions.\nUse \"@else if\" instead.";
                $message .= "\n    on line $line, column $column";

                if ($sourceUrl !== null) {
                    $message .= " of $sourceUrl";
                }

                $this->logger->warn($message, true);

                $this->scanner->setPosition($this->scanner->getPosition() - 2);

                return true;
            }
        }

        $this->scanner->setPosition($start);

        return false;
    }

    protected function children(callable $child): array
    {
        $this->scanner->expectChar('{');
        $this->whitespaceWithoutComments();
        $children = [];

        while (true) {
            switch ($this->scanner->peekChar()) {
                case '$':
                    $children[] = $this->variableDeclarationWithoutNamespace();
                    break;

                case '/':
                    switch ($this->scanner->peekChar(1)) {
                        case '/':
                            $children[] = $this->silentCommentStatement();
                            $this->whitespaceWithoutComments();
                            break;

                        case '*':
                            $children[] = $this->loudCommentStatement();
                            $this->whitespaceWithoutComments();
                            break;

                        default:
                            $children[] = $child();
                            break;
                    }
                    break;

                case ';':
                    $this->scanner->readChar();
                    $this->whitespaceWithoutComments();
                    break;

                case '}':
                    $this->scanner->expectChar('}');

                    return $children;

                default:
                    $children[] = $child();
                    break;
            }
        }
    }

    protected function statements(callable $statement): array
    {
        $statements = [];
        $this->whitespaceWithoutComments();

        while (!$this->scanner->isDone()) {
            switch ($this->scanner->peekChar()) {
                case '$':
                    $statements[] = $this->variableDeclarationWithoutNamespace();
                    break;

                case '/':
                    switch ($this->scanner->peekChar(1)) {
                        case '/':
                            $statements[] = $this->silentCommentStatement();
                            $this->whitespaceWithoutComments();
                            break;

                        case '*':
                            $statements[] = $this->loudCommentStatement();
                            $this->whitespaceWithoutComments();
                            break;

                        default:
                            $child = $statement();

                            if ($child !== null) {
                                $statements[] = $child;
                            }
                            break;
                    }
                    break;

                case ';':
                    $this->scanner->readChar();
                    $this->whitespaceWithoutComments();
                    break;

                default:
                    $child = $statement();

                    if ($child !== null) {
                        $statements[] = $child;
                    }
                    break;
            }
        }

        return $statements;
    }

    /**
     * Consumes a statement-level silent comment block.
     */
    private function silentCommentStatement(): SilentComment
    {
        $start = $this->scanner->getPosition();

        $this->scanner->expect('//');

        do {
            while (!$this->scanner->isDone() && !Character::isNewline($this->scanner->readChar())) {
                // Ignore the content of the comment
            }

            if ($this->scanner->isDone()) {
                break;
            }

            $this->whitespaceWithoutComments();
        } while ($this->scanner->scan('//'));

        if ($this->isPlainCss()) {
            $this->error('Silent comments aren\'t allowed in plain CSS.', $this->scanner->spanFrom($start));
        }

        $this->lastSilentComment = new SilentComment($this->scanner->substring($start), $this->scanner->spanFrom($start));

        return $this->lastSilentComment;
    }

    /**
     * Consumes a statement-level loud comment block.
     */
    private function loudCommentStatement(): LoudComment
    {
        $start = $this->scanner->getPosition();

        $this->scanner->expect('/*');

        $buffer = new InterpolationBuffer();
        $buffer->write('/*');

        while (true) {
            switch ($this->scanner->peekChar()) {
                case '#':
                    if ($this->scanner->peekChar(1) === '{') {
                        $buffer->add($this->singleInterpolation());
                    } else {
                        $buffer->write($this->scanner->readChar());
                    }
                    break;

                case '*':
                    $buffer->write($this->scanner->readChar());

                    if ($this->scanner->peekChar() !== '/') {
                        break;
                    }

                    $buffer->write($this->scanner->readChar());

                    return new LoudComment($buffer->buildInterpolation($this->scanner->spanFrom($start)));

                case "\r":
                    $this->scanner->readChar();

                    if ($this->scanner->peekChar() !== "\n") {
                        $buffer->write("\n");
                    }
                    break;

                case "\f":
                    $this->scanner->readChar();
                    $buffer->write("\n");
                    break;

                default:
                    $buffer->write($this->scanner->readUtf8Char());
            }
        }
    }
}
