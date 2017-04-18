<?php

namespace Md\ParserCombinators;

use function Parser\many;
use function Parser\result;
use Phunkie\Cats\Monad;
use const Phunkie\Functions\immlist\concat as concatenate;
use function Phunkie\Functions\immlist\concat;
use Phunkie\Types\ImmList;
use Phunkie\Types\Kind;
use Phunkie\Types\Pair;

/**
 * Class Parser<A>
 * wraps:
 *     string -> List<Pair(A , string)>
 */
class Parser implements Monad, Kind
{
    /**
     * @var callable string -> List<Pair(A, string)>
     */
    private $run;

    public function __construct(callable $run)
    {
        $this->run = $run;
    }

    /**
     * string -> List<Pair(A, string)>
     */
    public function run(string $input): ImmList
    {
        return ($this->run)($input);
    }

    /**
     * (A => Parser<B>) => Parser(B)
     */
    public function flatMap(callable $f): Kind
    {
        return new Parser(function(string $s) use ($f) {
            return $this->run($s)->flatMap(function(Pair $result) use ($f) {
                return $f($result->_1)->run($result->_2);
            });
        });
    }

    /**
     * (A => B) => Parser<B>
     */
    public function map(callable $f): Kind
    {
        return new Parser(function(string $s) use ($f) {
            return $this->run($s)->map(function(Pair $result) use ($f) {
                return \Pair($f($result->_1), $result->_2);
            });
        });
    }

    /**
     * (A => B) => (Parser<A> => Parser<B>)
     */
    public function lift($f): callable
    {
        return function (Parser $a) use ($f) {
            return new Parser(function($x) use ($a, $f) {
                return $a->run($f($x));
            });
        };
    }

    /**
     * B => Parser<B>
     */
    public function as($b): Kind
    {
        return result($b);
    }

    /**
     * () => Parser<Unit>
     */
    public function void(): Kind
    {
        return result(Unit());
    }

    /**
     * (A => B) => Parser<Pair<A,B>>
     */
    public function zipWith($f): Kind
    {
        return new Parser(function($x) use ($f) {
            return ImmList(Pair(Pair($x,  $f($x))), '');
        });
    }

    public function imap(callable $f, callable $g): Kind
    {
        return $this->map($f);
    }

    /**
     * Parser<Parser<A>> => Parser<A>
     */
    public function flatten(): Kind
    {
        return $this->run(_)->head->_1;
    }

    public function or(Parser $another): Parser
    {
        return new Parser(function(string $s) use ($another) {
            return concat($this->run($s), $another->run($s));
        });
    }

    public function sepBy1(Parser $sep)
    {
        return for_(
            __($x)->_($this),
            __($xs)->_(many(for_(
                __($_)->_($sep),
                __($y)->_($this)
            )->yields($y)))
        )->call(concatenate, $x, $xs);
    }
}