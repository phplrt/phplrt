# URL

Every part of an address, in the order
[RFC 3986](https://www.rfc-editor.org/rfc/rfc3986) writes them:

```
https://user:pass@example.com:8080/over/there?name=ferret&sort=asc#nose
```

Almost everything here is optional, and the root rule says exactly that: a
scheme and a host are required, the credentials, the port, the path, the query
and the fragment each carry a `?`. Reading `http://example.com` and the line
above takes the same seven rules.

The lexer keeps two ambiguities away from the parser. A percent escape belongs
to the token that contains it, so `%20` is part of a name rather than
punctuation of its own. And a number is only read as one where a name does not
carry on past it - `T_DIGITS` ends with `(?![a-zA-Z0-9.+\-%])` - so the `1a`
of a host is a name, not a number followed by a letter.

## Grammar

```pp2
/**
 * -----------------------------------------------------------------------------
 *  URL
 * -----------------------------------------------------------------------------
 *
 * The scheme, the credentials, the host, the port, the path, the query and the
 * fragment an address is written of.
 *
 * @see https://www.rfc-editor.org/rfc/rfc3986
 */

%pragma root Url

%skip  T_WHITESPACE      \s++

%token T_SCHEME_SEPARATOR ://

/**
 * A number is only read as one where a name does not go on past it, so the
 * "1a" of a host is a name rather than a number followed by a name.
 */
%token T_DIGITS          [0-9]++(?![a-zA-Z0-9.+\-%])

// A character written as itself, or as the "%" of its code
%token T_STRING          (?:[a-zA-Z~0-9]|%[0-9a-fA-F]{2})(?:[a-zA-Z0-9.+\-_]|%[0-9a-fA-F]{2})*+

%token T_DOUBLE_COLON    ::
%token T_COLON           :
%token T_SLASH           /
%token T_AT              @
%token T_QUESTION_MARK   \?
%token T_AMPERSAND       &
%token T_EQUAL           =
%token T_HASH            #
%token T_BRACKET_OPEN    \[
%token T_BRACKET_CLOSE   \]

Url
  : Scheme() ::T_SCHEME_SEPARATOR::
    Login()?
    Host()
    (::T_COLON:: Port())?
    (::T_SLASH:: Path()?)?
    Query()?
    Fragment()?
  ;

Scheme
  : <T_STRING>
  ;

// user:password@
Login
  : User() (::T_COLON:: Password())? ::T_AT::
  ;

User
  : Text()
  ;

Password
  : Text()
  ;

Host
  : ::T_SLASH::? HostName()
  ;

HostName
  : ::T_BRACKET_OPEN:: IPv6Host() ::T_BRACKET_CLOSE::
  | Text()
  ;

IPv6Host
  : ::T_DOUBLE_COLON::? Text() ((::T_COLON:: | ::T_DOUBLE_COLON::) Text())*
  ;

Port
  : <T_DIGITS>
  ;

Path
  : Text() (::T_SLASH:: Text())* ::T_SLASH::?
  ;

Query
  : ::T_QUESTION_MARK:: Search()
  ;

Search
  : SearchParameter() (::T_AMPERSAND:: SearchParameter())*
  ;

SearchParameter
  : Text() (::T_EQUAL:: Text())?
  ;

Fragment
  : ::T_HASH:: Text()
  ;

Text
  : <T_STRING>
  | <T_DIGITS>
  ;
```

## Usage

```php
use Phplrt\Compiler\Compiler;
use Phplrt\Source\File;
use Phplrt\Source\Source;

$parser = new Compiler()
    ->load(new File(__DIR__ . '/grammar.pp3'))
    ->getParser();

$url = $parser->parse(new Source('https://example.com:8080/a/b?c=d#e'));
```

`parse_url()` is faster and shorter, and this grammar is still worth reading:
it says out loud what that function decides quietly, and it tells you *where*
an address goes wrong instead of returning `false`.

> **25+ more grammars.** [phplrt/grammars](https://github.com/phplrt/grammars)
> collects ready to read grammars for real languages - JSON5, TSV, semantic
> versions, DQL, PHQL, JMS types, PSR-5 and Doctrine annotations, Symfony
> expressions, Go! AOP pointcuts, Praspel contracts and more - each with sample
> inputs and a test that keeps it honest.
