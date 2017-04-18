<?php

namespace Parser {

    use Md\ParserCombinators\Parser;
    use const Phunkie\Functions\immlist\concat;
    use const Phunkie\Functions\numbers\negate;
    use function Success as Valid;
    use function Failure as Invalid;

    function result($a): Parser
    {
        return new Parser(function (string $s) use ($a) {
            return ImmList(Pair($a, $s));
        });
    }

    function zero(): Parser
    {
        return new Parser(function ($s) {
            return Nil();
        });
    }

    function item(): Parser
    {
        return new Parser(function (string $s) {
            return strlen($s) == 0 ? Nil() : ImmList(Pair($s[0], substr($s, 1)));
        });
    }

    function seq(Parser $p, Parser $q): Parser
    {
        return for_(
            __($x)->_($p),
            __($y)->_($q)
        )->yields($x, $y);
    }

    function sat(callable $predicate): Parser
    {
        return item()->flatMap(function ($x) use ($predicate) {
            return $predicate($x) ? result($x) : zero();
        });
    }

    function not($c): Parser
    {
        return sat(function($s) use ($c) { return $s !== $c; });
    }

    function manyNot($c): Parser
    {
        return many1(not($c));
    }

    function char($c): Parser
    {
        return sat(function ($input) use ($c) {
            return $input === $c;
        });
    }

    function digit(): Parser
    {
        return sat('is_numeric');
    }

    function lower(): Parser
    {
        return sat('ctype_lower');
    }

    function upper(): Parser
    {
        return sat('ctype_upper');
    }

    function plus(Parser $p, Parser $q): Parser
    {
        return $p->or($q);
    }

    function letter(): Parser
    {
        return plus(lower(), upper());
    }

    function alphanum(): Parser
    {
        return plus(letter(), digit());
    }

    function word(): Parser
    {
        $nonEmptyWord = letter()->flatMap(function ($x) {
            return word()->map(function ($xs) use ($x) {
                return $x . $xs;
            });
        });

        return plus($nonEmptyWord, result(''));
    }

    function string($s): Parser
    {
        return strlen($s) ?

            for_(
                __($c)->_(char($s[0])),
                __($cs)->_(string(substr($s, 1)))
            )->call(concat, $c, $cs) :

            result('');
    }

    function many(Parser $p): Parser
    {
        return plus($p->flatMap(function ($x) use ($p) {
            return many($p)->map(function ($xs) use ($x) {
                return $x . $xs;
            });
        }), result(''));
    }

    function many1(Parser $p): Parser
    {
        return for_(
            __($x)->_($p),
            __($xs)->_(many($p))
        )->call(concat, $x, $xs);
    }

    function nat(): Parser
    {
        return many1(digit())->map(function ($xs) {
            return (int)$xs;
        });
    }

    function int()
    {
        return plus(for_(
            __($_)->_(char('-')),
            __($n)->_(nat())
        )->call(negate, $n), nat());
    }

    function sepBy1(Parser $p, Parser $sep)
    {
        return $p->sepBy1($sep);
    }

    function surrounded(Parser $open, Parser $p, Parser $close)
    {
        return for_(
            __($_)->_($open),
            __($ns)->_($p),
            __($_)->_($close)
        )->yields($ns);
    }

    function ints()
    {
        return surrounded(
            char('['),
            int()->sepBy1(char(',')),
            char(']')
        );
    }
}
/**
 * Json parser
 */
namespace Md\ParserCombinators\JsonParser {

    use Md\ParserCombinators\Parser;
    use function Parser\char;
    use function Parser\manyNot;
    use function Parser\nat;
    use function Parser\plus;
    use function Parser\result;
    use function Parser\string;
    use function Parser\word;
    use Phunkie\Types\ImmMap;

    function json_string(): Parser
    {
        return for_(
            __($_)->_(char('"')),
            __($string)->_(manyNot('"')),
            __($_)->_(char('"'))
        )->call(function($s){return new JsonString($s);}, $string);
    }

    function json_boolean(): Parser
    {
        return string("true")->or(string("false"))->map(function($result) {
            return new JsonBoolean($result === 'true');
        });
    }

    function json_null(): Parser
    {
        return string("null")->map(function($_) {
            return new JsonNull();
        });
    }

    function json_number(): Parser
    {
        return nat()->map(function($n) {
            return new JsonNumber($n);
        });
    }

    function json_array(): Parser
    {
        return char('[')->flatMap(function($_) {
            return sepBy1array(json_value(), char(','))->flatMap(function($elements) {
                return char(']')->map(function($_) use ($elements) {
                    return new JsonArray($elements);
                });
            });
        });
    }

    function json_object(): Parser
    {
        return char('{')->flatMap(function($_) {
            return sepBy1Map(word()->flatMap(function($key) {
                return char(':')->flatMap(function($colon) use ($key) {
                    return json_value()->map(function($value) use ($key) {
                        return ImmMap($key , $value);
                    });
                });
            }), char(','))->flatMap(function($pairs) {
                return char('}')->map(function() use ($pairs) {
                    return new JsonObject($pairs);
                });
            });
        });
    }

    function json_value(): Parser
    {
        return json_string()
            ->or(json_boolean())
            ->or(json_null())
            ->or(json_number())
            ->or(json_array())
            ->or(json_object());
    }

    function sepBy1array(Parser $p, Parser $sep)
    {
        return $p->flatMap(function($x) use ($sep, $p) {
            return manyArray(
                $sep->flatMap(function($_) use ($p) {
                    return $p->map(function($y) {
                        return $y;
                    });
                }))->map(function($xs) use ($x) {
                if (is_array($xs)) { return array_merge([$x], $xs); }
                return [$x, $xs];
            });
        });
    }

    function manyArray(Parser $p): Parser
    {
        return plus($p->flatMap(function ($x) use ($p) {
            return manyArray($p)->map(function ($xs) use ($x) {
                if (is_array($xs)) { return array_merge([$x], $xs); }
                return [$x, $xs];
            });
        }), result([]));
    }

    function sepBy1Map(Parser $p, Parser $sep)
    {
        return $p->flatMap(function($x) use ($sep, $p) {
            return manyMap(
                $sep->flatMap(function($_) use ($p) {
                    return $p->map(function($y) {
                        return $y;
                    });
                }))->map(function($xs) use ($x) {
                if ($xs == '') return $x;
                if ($x instanceof ImmMap && $xs instanceof ImmMap) {
                    foreach ($xs->iterator() as $key => $value) {
                        $x = $x->plus($key, $value);
                    }
                    return $x;
                }
                return ImmMap($x, $xs);
            });
        });
    }

    function manyMap(Parser $p): Parser
    {
        return plus($p->flatMap(function ($x) use ($p) {
            return manyMap($p)->map(function ($xs) use ($x) {
                if ($xs == '')
                    return $x;
                if ($x instanceof ImmMap && $xs instanceof ImmMap) {
                    foreach ($xs->iterator() as $key => $value) {
                        $x = $x->plus($key, $value);
                    }
                    return $x;
                }
                return ImmMap($x, $xs);
            });
        }), result(''));
    }
}

