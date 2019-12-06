<?php

$this->preload(Phplrt\Grammar\Rule::class);
$this->preload(Phplrt\Grammar\Production::class);
$this->preload(Phplrt\Grammar\Terminal::class);

foreach ($this->getRules() as $id => $rule) {
    $this->preload(\get_class($rule));
}

foreach ($this->getImports() as $fqn => $import) {
    $description = [
        '/**',
        ' * Note: This class was automatically imported from ' . $fqn,
        ' * @created ' . \date(\DateTime::RFC3339),
        ' * @internal this class for internal usage only',
        ' * @package ' . $fqn,
        ' * @generator \\' . static::class,
        ' */'
    ];

    echo \implode("\n", $description) . "\n";

    if (\strpos($import, 'class') === 0) {
        echo 'final ';
    }

    echo $import . "\n\n";
}


