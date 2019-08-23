# Concepts

### Contracts

Contracts are a set of portable interfaces that provide poor connectivity 
between components. Thus, it is allowed to use third-party implementations 
as one of the parts of the system.

| Package                                                                                 | Version                                                                                  |
| --------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------- |
| [phplrt/ast-contracts](https://packagist.org/packages/phplrt/ast-contracts)             | ![phplrt/ast-contracts](https://poser.pugx.org/phplrt/ast-contracts/version)             |
| [phplrt/lexer-contracts](https://packagist.org/packages/phplrt/lexer-contracts)         | ![phplrt/lexer-contracts](https://poser.pugx.org/phplrt/lexer-contracts/version)         |
| [phplrt/parser-contracts](https://packagist.org/packages/phplrt/parser-contracts)       | ![phplrt/parser-contracts](https://poser.pugx.org/phplrt/parser-contracts/version)       |

### Runtime

Runtime components are a set of packages that provide the execution of 
parser instructions.

> As one of the ideas, it is possible to include this set of components in the 
> final assembly of the parser so that he does not depend on external packages.

| Package                                                             | Required | Version                                                              |
| ------------------------------------------------------------------- | :------: | -------------------------------------------------------------------- |
| [phplrt/lexer](https://packagist.org/packages/phplrt/lexer)         | ✔        | ![phplrt/lexer](https://poser.pugx.org/phplrt/lexer/version)         |
| [phplrt/parser](https://packagist.org/packages/phplrt/parser)       | ✔        | ![phplrt/parser](https://poser.pugx.org/phplrt/parser/version)       |
| [phplrt/ast](https://packagist.org/packages/phplrt/ast)             | ✕        | ![phplrt/ast](https://poser.pugx.org/phplrt/ast/version)             |
| [phplrt/position](https://packagist.org/packages/phplrt/position)   | ✕        | ![phplrt/position](https://poser.pugx.org/phplrt/position/version)   |
| [phplrt/visitor](https://packagist.org/packages/phplrt/visitor)     | ✕        | ![phplrt/visitor](https://poser.pugx.org/phplrt/visitor/version)     |

### Development

This is a set of tools for developing and debugging software that can be 
included as DEV dependencies and are not required for the correct operation 
of the final software.

| Package                                                             | Version                                                              |
| ------------------------------------------------------------------- | -------------------------------------------------------------------- |
| [phplrt/compiler](https://packagist.org/packages/phplrt/compiler)   | ![phplrt/compiler](https://poser.pugx.org/phplrt/compiler/version)   |
| [phplrt/dumper](https://packagist.org/packages/phplrt/dumper)       | ![phplrt/dumper](https://poser.pugx.org/phplrt/dumper/version)       |
| [phplrt/assembler](https://packagist.org/packages/phplrt/assembler) | ![phplrt/assembler](https://poser.pugx.org/phplrt/assembler/version) |

