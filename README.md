# PathKit

This package is part of Fruit Framework.

PathKit is set of tools handling path in filesystem.

[![Build Status](https://travis-ci.org/Ronmi/fruit-pathkit.svg)](https://travis-ci.org/Ronmi/fruit-pathkit)

## Synopsis

```php

// let's say if current working directory is /work/temp
$path = new Path('../my.file'); // path is '../my.file'
$path->isAbsolute(); // false
$path->expand(); // /work/temp/../my.file
$path->normalize(); // /work/my.file
$path->within(); // false, because /work/my.file does not belong to /work/temp
$path->within('/work'); // true
$path->relative(); // ../my.file
$path->relative('/another/work'); // ../../work/my.file
```

## License

Any version of MIT, GPL or LGPL.
