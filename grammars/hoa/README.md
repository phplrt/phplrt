# Hoa PP

The grammar description format of `Hoa\Compiler`, and the one PP2 descends
from. A directive and the name of a rule begin a line; what a rule recognizes
is written indented under its name.

The rule bodies follow [`Llk/Llk.pp`](https://github.com/hoaproject/Compiler/blob/master/Llk/Llk.pp);
the file level follows `Llk::parsePP()`. Samples are real Hoa grammars.
Licence: New BSD.
