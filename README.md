# SPE - Simple PHP Examples

_Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)_

A very simple PHP8.4 "framework" that will be expanded to include more small
project examples building on the first very lean foundation and incorporating
additional functionality in each successive example. This is not a repository
of all the attributes of PHP8+, there are already many other great projects
and pages that provide excellent PHP8 guides, but rather a series of examples
that are fully developed under PHP8.4 and taking advantage of any new PHP8.4
constructs where it makes sense.

Each folder will contain a more comprehensive example of a working, and
hopefully useful, sub-project with a README.md explaining each example. This
README will provide an overview and index of all examples and some hints that
apply to all the sub-project examples.

- [01-Simple]

  At 100 LOC this is about the simplest 3 page example of this particular
  framework style I could come up with. It is a self-contained single script
  with all code encapsulated within a single anonymous class and one `echo`.

  Key Features:
    - Single File Architecture
    - Type Safety
    - Modern PHP 8.4
    - Clean URL Routing
    - XSS Protection
    - Responsive Design

- [02-Styled]

  Funcionally similar to the above barebones example but with a small amount
  of inline CSS to provide a minimum of style along with the Roboto font from
  Google CDN.

  Key Differences:
    - Integrated Bootstrap 5 styling
    - Flexible navigation system
    - Modern PHP 8.x features

- [03-Plugins]

  A simple example of providing "plugins" which are basically the model of
  the traditional MVC coding style. It also includes a simple AJAX link on
  the About page that dumps the main global output array using the ultra
  simple remote XHR API. There is also an example of passing a success/error
  message back into the same page but it will be replaced with session vars
  in one of the next examples.

  Key Components:
    - Config Class
    - Init Class
    - Plugin Abstract Class
    - Concrete Plugin Classes
    - Util Class

- [04-Themes]

  Extend the above example to include basic themeing classes and methods.

  TODO: write doc

- [05-Autoload]

  Add PSR-4 autoload via composer.

  Key Features:
    - Plugin Architecture
    - Theme System
    - Context Management
    - Logging
    - Responsive Design
    - AJAX Support

- [06-Session]

  TODO: write doc

- [07-PDO]

  TODO: write doc

- [08-Users]

  TODO: write doc

- [09-Auth]

  TODO: write doc

- [10-Files]

  TODO: write doc


The associated example README files will act as both code comments and
general documentation for each project.

[01-Simple]:   https://github.com/markc/spe/tree/master/01-Simple/README.md
[02-Styled]:   https://github.com/markc/spe/tree/master/02-Styled/README.md
[03-Plugins]:  https://github.com/markc/spe/tree/master/03-Plugins/README.md
[04-Themes]:   https://github.com/markc/spe/tree/master/04-Themes/README.md
[05-Autoload]: https://github.com/markc/spe/tree/master/05-Autoload/README.md
[06-Session]: https://github.com/markc/spe/tree/master/06-Session/README.md
[07-PDO]: https://github.com/markc/spe/tree/master/07-PDO/README.md
[08-Users]: https://github.com/markc/spe/tree/master/08-Users/README.md
[09-Auth]: https://github.com/markc/spe/tree/master/09-Auth/README.md
[10-Files]: https://github.com/markc/spe/tree/master/10-Files/README.md
