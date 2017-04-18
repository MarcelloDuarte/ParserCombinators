<?php

use function Md\ParserCombinators\JsonParser\json_object;
use function Md\ParserCombinators\JsonParser\json_value;
use Md\ParserCombinators\JsonParser\JsonArray;
use Md\ParserCombinators\JsonParser\JsonBoolean;
use Md\ParserCombinators\JsonParser\JsonNull;
use Md\ParserCombinators\JsonParser\JsonNumber;
use Md\ParserCombinators\JsonParser\JsonObject;
use Md\ParserCombinators\JsonParser\JsonString;
use function Parser\int;
use function Parser\ints;
use function Parser\many;
use function Parser\many1;
use function Parser\nat;
use function Parser\result;
use function Parser\zero;
use function Parser\seq;
use function Parser\item;
use function Parser\sat;
use function Parser\char;
use function Parser\upper;
use function Parser\lower;
use function Parser\digit;
use function Parser\letter;
use function Parser\alphanum;
use function Parser\word;
use function Parser\string;

describe("Parser", function(){
    context("Basic parsers", function() {
        describe("result", function() {
            it("succeeds without consuming any of the string", function() {
                expect(result("hello")->run("world"))->toEqual(ImmList(Pair("hello", "world")));
            });
        });

        describe("zero", function(){
            it("always return an empty list, which means failure", function(){
                expect(zero()->run("world"))->toEqual(Nil());
            });
        });

        describe("item", function() {
            it("parses a string and returns the first character", function() {
                expect(item()->run("hello"))->toEqual(ImmList(Pair('h', "ello")));
            });
            it("returns an empty list for an empty string", function() {
                expect(item()->run(""))->toEqual(Nil());
            });
            it("returns the character for a single character string", function() {
                expect(item()->run('h'))->toEqual(ImmList(Pair('h', "")));
            });
        });

        describe("seq", function() {
            it("applies parsers in sequence", function() {
                expect(seq(item(), item())->run("hello"))->toEqual(ImmList(Pair(Pair('h', 'e'), "llo")));
            });
        });

        describe("sat", function() {
            it("parsers one character when predicate matches", function(){
                expect(sat("is_numeric")->run("4L"))->toEqual(ImmList(Pair('4', 'L')));
            });
            it("returns an empty list when predicate does not match", function(){
                expect(sat("is_numeric")->run("L4"))->toEqual(Nil());
            });
        });
    });

    context("Sequencing combinators", function() {
        describe("char", function() {
            it("parses a character", function() {
                expect(char('h')->run("hello"))->toEqual(ImmList(Pair('h', "ello")));
            });
            it("returns an empty list when character does not match", function() {
                expect(char('x')->run("hello"))->toEqual(Nil());
            });
        });

        describe("digit", function() {
            it("parses a digit", function() {
                expect(digit()->run("42"))->toEqual(ImmList(Pair('4', '2')));
            });
            it("returns an empty list when digit does not match", function() {
                expect(digit()->run("a42"))->toEqual(Nil());
            });
        });

        describe("lower", function() {
            it("parses a lowercase character", function() {
                expect(lower()->run("hello"))->toEqual(ImmList(Pair('h', "ello")));
            });
            it("returns an empty list when lower does not match", function() {
                expect(lower()->run("Hello"))->toEqual(Nil());
            });
        });

        describe("upper", function() {
            it("parses a upper case character", function() {
                expect(upper()->run("Hello"))->toEqual(ImmList(Pair('H', "ello")));
            });
            it("returns an empty list when upper does not match", function() {
                expect(upper()->run("eello"))->toEqual(Nil());
            });
        });
    });

    describe("another use of flatMap", function() {
        it("can combine the result of 2 parsers", function() {
            expect(
                lower()->flatMap(function($x){
                    return lower()->map(function($y) use ($x) {
                        return $x . $y;
                    });
                })->run("abcd"))->toEqual(ImmList(Pair("ab","cd")));
        });
    });

    context("Choice combinators", function() {
        describe("letter", function() {
            it("can combine parsers to parse letters", function() {
                expect(letter()->run("hello"))->toEqual(ImmList(Pair('h', "ello")));
                expect(letter()->run("Hello"))->toEqual(ImmList(Pair('H', "ello")));
                expect(letter()->run("5ello"))->toEqual(Nil());
            });
        });

        describe("alphanum", function() {
            it("can combine parsers to parse alphanum", function() {
                expect(alphanum()->run("hello"))->toEqual(ImmList(Pair('h', "ello")));
                expect(alphanum()->run("Hello"))->toEqual(ImmList(Pair('H', "ello")));
                expect(alphanum()->run("5ello"))->toEqual(ImmList(Pair('5', "ello")));
                expect(alphanum()->run("#ello"))->toEqual(Nil());
            });
        });
    });

    context("Recursive combinators", function() {
        describe("word", function() {
            it("recognises entire words out of a string", function() {
                expect(word()->run("Yes!"))
                    ->toEqual(ImmList(
                        Pair("Yes", '!'),
                        Pair("Ye", "s!"),
                        Pair('Y', "es!"),
                        Pair("", "Yes!")
                    ));
            });
        });

        describe("string", function() {
            it("parses a string", function() {
                expect(string("hello")->run("helloworld"))->toEqual(ImmList(Pair("hello", "world")));
                expect(string("helicopter")->run("helloworld"))->toEqual(Nil());
            });
        });
    });

    context("Simple repetitions", function() {
        describe("many", function() {
            it("generalises repetition", function() {
                expect(many(char('t'))->run("ttthat's all folks"))->toEqual(ImmList(
                    Pair("ttt", "hat's all folks"),
                    Pair("tt", "that's all folks"),
                    Pair('t', "tthat's all folks"),
                    Pair("", "ttthat's all folks")
                ));
            });
            it ("never produces errors", function() {
                expect(many(char('x'))->run("ttthat's all folks"))->toEqual(ImmList(
                    Pair("", "ttthat's all folks")
                ));
            });
        });
        describe("many1", function() {
            it("does not have the empty result of many", function() {
                expect(many1(char('t'))->run("ttthat's all folks"))->toEqual(ImmList(
                    Pair("ttt", "hat's all folks"),
                    Pair("tt", "that's all folks"),
                    Pair('t', "tthat's all folks")
                ));
            });
            it("may produce an error", function() {
                expect(many1(char('x'))->run("ttthat's all folks"))->toEqual(Nil());
            });
        });
        describe("nat", function() {
            it("can be defined with repetition", function() {
                expect(nat()->run("34578748fff"))->toEqual(ImmList(
                    Pair(34578748, "fff"),
                    Pair(3457874, "8fff"),
                    Pair(345787, "48fff"),
                    Pair(34578, "748fff"),
                    Pair(3457, "8748fff"),
                    Pair(345, "78748fff"),
                    Pair(34, "578748fff"),
                    Pair(3, "4578748fff")
                ));
                expect(nat()->run("34578748fff")->head->_1)->toEqual(34578748);
            });
        });
        describe("int", function() {
            it("can be defined from char('-') and nat", function() {
                expect(int()->run("-251asdfasdf")->head->_1)->toEqual(-251);
                expect(int()->run("251asdfasdf")->head->_1)->toEqual(251);
            });
        });
    });

    context("Repetition with separators", function() {
        describe("ints, a proper grammar [1,2,3,4]", function() {
            it("parsers a string representing an array of ints", function() {
                expect(ints()->run("[1,2,3,4]"))->toEqual(ImmList(Pair("1234", "")));
            });
        });
        describe("sepby1", function() {
            it("applies a parser several times separated by another parser application", function() {
                expect(letter()->sepby1(digit())->run("a1b2c3d4"))->toEqual(
                    ImmList(Pair("abcd", '4'), Pair("abc", "3d4"), Pair("ab", "2c3d4"), Pair('a', "1b2c3d4"))
                );
            });
        });
    });

    context("Json parser", function() {
        describe("json_object", function() {
            it("parses json objects with no space or fancy digits", function() {
                expect((string)json_value()->run(
                    '{a:1,b:null,c:true,d:{x:"a",b:[1,2,3]}}'
                )->head->_1)->toBe((string)new JsonObject(
                    ImmMap([
                        "a" => new JsonNumber(1),
                        "b" => new JsonNull(),
                        "c" => new JsonBoolean(true),
                        "d" => new JsonObject(
                            ImmMap([
                                "x" => new JsonString("a"),
                                "b" => new JsonArray([
                                    new JsonNumber(1),
                                    new JsonNumber(2),
                                    new JsonNumber(3)
                                ])
                            ])
                        )
                    ])
                ));
            });
        });
    });
});