<p align="center">
    <a href="https://phplrt.org"><img src="https://avatars2.githubusercontent.com/u/49816277?s=128" width="128" alt="Phplrt" /></a>
</p>

## Grammars

This repository is a collection of formal grammar examples written for 
[phplrt v4](https://github.com/phplrt/phplrt).

> [!IMPORTANT]
> These grammars are **examples**, not reference implementations. Each is
> checked against the samples shipped next to it, which is a long way from
> covering the language it describes: a grammar may read something the language
> forbids, refuse something the language allows, or lag behind the version it
> was written from.
>
> Do not treat one as a specification, and do not rely on one in production
> without reading it first. We keep improving them, and
> [issues](https://github.com/phplrt/phplrt/issues) and pull requests pointing
> at a case that is read wrongly are welcome.

The root directory name is the all-lowercase name of the language or file format
parsed by the grammar. For example, `json`, `json5`, `graphql`, `c`, etc.

The `src/` directory contains a set of shared helper classes.

The `tests/` directory contains a set of tests to validate the grammar for
correctness.

### PHP Specific

- [Composer Version Constraint](./composer)
- [Doctrine Annotations](./doctrine-annotations)
- [DQL (Doctrine Query Language)](./dql)
- [PP Grammar (Hoa)](./pp)
- [PP2 Grammar (phplrt)](./pp2)
- [PP3 Grammar (phplrt)](./pp3)
- [Hoa Ruler](./hoa-ruler)
- [Hoa Math](./hoa-math)
- [Go! AOP Pointcut](./go-aop)
- [JMS Serializer Type](./jms)
- [PCRE (Hoa PHPStan Fork)](./hoa-pcre)
- [PHQL (Phalcon)](./phql)
- [Praspel](./praspel)
- [PHPDoc Types (PHPStan)](./phpdoc-phpstan)
- [PHPDoc Types (PSR-5)](./phpdoc-psr-5)
- [PHPDoc Types (TypeLang)](./phpdoc-type-lang)

### Common

- [Cron Expression](./cron)
- [CSV](./csv)
- [EBNF](./ebnf)
- [Graphviz DOT](./dot)
- [JSON](./json)
- [JSON5](./json5)
- [Newick](./newick)
- [Semantic Versioning](./semver)
- [TSV](./tsv)
- [URL](./url)

Please note that some code examples are taken from the
[ANTLR v4 repository](https://github.com/antlr/grammars-v4) and adapted for
phplrt v4. The repository does not contain a license, but ANTLR is distributed
[under the BSD license](https://www.antlr.org/license.html).

### Resources

- [Documentation](https://github.com/phplrt/phplrt/blob/master/README.md)
- [Repository](https://github.com/phplrt/phplrt)
    - [Issues](https://github.com/phplrt/phplrt/issues)
    - [Pull Requests](https://github.com/phplrt/phplrt/pulls)
