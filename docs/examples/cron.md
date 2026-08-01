# Cron Expression

The five (or six) fields of a `crontab` line, the `@daily` shorthands and the
extensions almost every scheduler ends up supporting:

```
*/15 * * * *          every quarter of an hour
0 9-17 * * MON-FRI    hourly, during office hours
0 0 1,15 JAN,JUL *    the 1st and the 15th of January and July
0 12 L * 5#3          noon of the last day, and of the third Friday
0 0 1 1 * 2026        new year, in a year of its own
@daily
```

The hard part of cron is not its shape - a field is a list of ranges with an
optional step - but its spelling. `L`, `15W` and `5#3` each carry a number
inside them, so the lexer reads each as one token: splitting `5#3` into a
number, a hash and a number would leave the parser to put it back together
for no gain.

The optional year field needs no rule of its own either: `Field(){5,6}` is a
repetition with bounds, so one line reads both the classic five field
expression and the six field one.

## Grammar

```pp3
/**
 * -----------------------------------------------------------------------------
 *  Cron Expression
 * -----------------------------------------------------------------------------
 *
 * When a job is due: the minute, the hour, the day of the month, the month and
 * the day of the week it runs on.
 *
 * @see https://man7.org/linux/man-pages/man5/crontab.5.html
 */

%pragma root Expression

%skip  T_WHITESPACE   \h++

// @yearly, @monthly, @weekly, @daily, @hourly, @reboot
%token T_NICKNAME     @(?i)(?:annually|yearly|monthly|weekly|daily|midnight|hourly|reboot)\b

// JAN..DEC, MON..SUN
%token T_NAME         (?i)(?:jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec|mon|tue|wed|thu|fri|sat|sun)\b

/**
 * The last day, the weekday nearest a day and the n-th weekday of a month are
 * written next to the number they belong to, so each is read as a single
 * token: "L", "5L", "15W", "5#3".
 */
%token T_NTH          [0-9]++#[0-9]++
%token T_LAST         [0-9]*+(?i)L\b
%token T_NEAREST      [0-9]++(?i)W\b

%token T_NUMBER       [0-9]++
%token T_ASTERISK     \*
%token T_QUESTION     \?
%token T_COMMA        ,
%token T_DASH         -
%token T_SLASH        /
%token T_NEWLINE      \R

Expression
  : <T_NICKNAME> ::T_NEWLINE::?
  | Field(){5,6} ::T_NEWLINE::?
  ;

// A field is written of the values it matches, separated by commas
Field
  : Value() (::T_COMMA:: Value())*
  ;

Value
  : Range() Step()?
  ;

Range
  : <T_ASTERISK>
  | <T_QUESTION>
  | Unit() (::T_DASH:: Unit())?
  ;

Unit
  : <T_NTH>
  | <T_LAST>
  | <T_NEAREST>
  | Number()
  ;

Number
  : <T_NUMBER>
  | <T_NAME>
  ;

// Every n-th value of the range: "*/15"
Step
  : ::T_SLASH:: <T_NUMBER>
  ;
```

## Usage

Nothing needs building here - the question is only whether the expression is
one, which is what [`analyze()`](/docs/parser#analysing-a-source) answers
without running a single reducer:

```php
use Phplrt\Compiler\Compiler;
use Phplrt\Parser\Analysis\Mode;
use Phplrt\Parser\Analysis\Result\SuccessfulResult;
use Phplrt\Source\File;
use Phplrt\Source\Source;

$parser = new Compiler()
    ->load(new File(__DIR__ . '/grammar.pp3'))
    ->getParser();

$parser->analyze(new Source('*/15 * * * *'), Mode::SyntaxCheck) instanceof SuccessfulResult; // true
$parser->analyze(new Source('*/15 * *'), Mode::SyntaxCheck) instanceof SuccessfulResult;     // false
```

`Mode::SyntaxCheck` skips value building entirely, which is the cheapest way
to ask "is this valid" of user input.

> **25+ more grammars.** [phplrt/grammars](https://github.com/phplrt/grammars)
> collects ready to read grammars for real languages - JSON5, TSV, semantic
> versions, DQL, PHQL, JMS types, PSR-5 and Doctrine annotations, Symfony
> expressions, Go! AOP pointcuts, Praspel contracts and more - each with sample
> inputs and a test that keeps it honest.
