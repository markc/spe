## spe/03-Plugins

_Copyright (C) 2015 Mark Constable <markc@renta.net> (AGPL-3.0)_

# PHP 8.4 Plugin-Based Framework

This repository contains a simple PHP 8.4 compatible framework designed to provide a core structure for experimental development. The framework is built around a plugin-based architecture, allowing for easy extension and customization. Below is a detailed explanation of how the framework works, its components, and how to use it.

## Table of Contents
1. [Overview](#overview)
2. [Components](#components)
   - [Config Class](#config-class)
   - [Init Class](#init-class)
   - [Plugin Abstract Class](#plugin-abstract-class)
   - [Concrete Plugin Classes](#concrete-plugin-classes)
   - [Util Class](#util-class)
3. [Usage](#usage)
4. [License](#license)

## Overview

The framework is designed to be lightweight and modular, making it easy to extend with new plugins. It uses a combination of object-oriented programming and modern PHP features to provide a flexible and maintainable structure.

## Components

### Config Class

The `Config` class serves as a configuration container. It holds default values for various settings, including input parameters, output components, and navigation links.

```php
class Config
{
    public function __construct(
        public string $email = 'markc@renta.net',
        public array $in = [
            'l' => '',      // Log (message)
            'm' => 'read',  // Method (action)
            'o' => 'home',  // Object (plugin)
            'x' => '',      // XHR (request)
        ],
        public array $out = [
            'doc'   => 'SPE::03',
            'css'   => '',
            'log'   => '',
            'nav1'  => '',
            'head'  => 'Plugin PHP Example',
            'main'  => 'Error: missing plugin!',
            'foot'  => 'Copyright (C) 2015-2025 Mark Constable (AGPL-3.0)',
            'js'    => '',
        ],
        public array $nav1 = [
            ['Home', '?o=home'],
            ['About', '?o=about'],
            ['Contact', '?o=contact'],
        ]
    )
    {
        Util::elog(__METHOD__);
    }
}
```

### Init Class

The `Init` class is responsible for initializing the application. It processes input parameters, handles plugin execution, and generates the final output.

```php
readonly class Init
{
    public function __construct(
        private Config $config
    )
    {
        Util::elog(__METHOD__);

        // Process input parameters
        foreach ($this->config->in as $k => $v)
        {
            $this->config->in[$k] = $_REQUEST[$k] ?? $v;
            if (isset($_REQUEST[$k]))
            {
                $this->config->in[$k] = htmlentities(trim($_REQUEST[$k]));
            }
        }

        // Handle plugin execution (o=plugin object/class, m=action method)
        $object = $this->config->in['o'];
        $method = $this->config->in['m'];
        $this->config->out['main'] = match (true)
        {
            !class_exists($object) => "Error: no plugin object!",
            !method_exists($object, $method) => "Error: no plugin method!",
            default => (new $object($this->config))->$method()
        };

        // Process output components
        foreach ($this->config->out as $k => $v)
        {
            if (method_exists($this, $k))
            {
                $this->config->out[$k] = $this->$k();
            }
        }
    }

    public function __toString(): string
    {
        Util::elog(__METHOD__);

        if ($this->config->in['x'])
        {
            $xhr = $this->config->out[$this->config->in['x']] ?? '';
            if ($xhr) return $xhr;
            header('Content-Type: application/json');
            return json_encode($this->config->out, JSON_PRETTY_PRINT);
        }
        return $this->html();
    }

    // Other methods for generating CSS, JS, navigation, etc.
}
```

### Plugin Abstract Class

The `Plugin` abstract class provides a base for all plugins. It includes default implementations for common CRUD operations, which can be overridden by concrete plugin classes.

```php
abstract class Plugin
{
    protected string $buf = '';

    public function __construct(
        protected readonly Config $config
    )
    {
        Util::elog(__METHOD__);
    }

    public function __toString(): string
    {
        Util::elog(__METHOD__);

        return $this->buf;
    }

    abstract public function read(): string;

    public function create(): string
    {
        Util::elog(__METHOD__);

        return "Plugin::create() not implemented yet!";
    }

    public function update(): string
    {
        Util::elog(__METHOD__);

        return "Plugin::update() not implemented yet!";
    }

    public function delete(): string
    {
        Util::elog(__METHOD__);

        return "Plugin::delete() not implemented yet!";
    }

    public function list(): string
    {
        Util::elog(__METHOD__);

        return "Plugin::list() not implemented yet!";
    }
}
```

### Concrete Plugin Classes

Concrete plugin classes extend the `Plugin` class and provide specific implementations for the `read` method. Examples include `Home`, `About`, and `Contact` classes.

```php
final class Home extends Plugin
{
    public function read(): string
    {
        Util::elog(__METHOD__);

        return '
            <div class="px-4 py-5 bg-light rounded-3 border">
                <div class="row d-flex justify-content-center">
                <div class="col-lg-8 col-md-10 col-sm-12">
                    <h1 class="display-5 fw-bold text-center">' . $this->config->out['head'] . '</h1>
                    <p class="lead mb-4">
This is an example of a simple PHP8.4 "framework" to provide the core
structure for further experimental development with both the framework
design and some of the new features of PHP8.4.
                    </p>
                    <form method="post">
                            <div class="d-flex flex-column flex-sm-row gap-2 mb-4">
                            <button type="button" class="btn btn-success flex-fill" onclick="showToast(\'Everything is working great!\', \'success\');">Success Message</button>
                            <button type="button" class="btn btn-danger flex-fill" onclick="showToast(\'Something went wrong!\', \'danger\');">Danger Message</button>
                        </div>
                    </form>
                    <pre id="dbg" class="text-start overflow-auto"></pre>
                </div>
                </div>
            </div>';
    }
}
```

### Util Class

The `Util` class provides utility functions, such as logging.

```php
final class Util
{
    public static function elog(string $content): void
    {
        if (defined('DBG') && DBG)
        {
            error_log($content);
        }
    }
}
```

## Usage

To use this framework, follow these steps:

1. **Clone the repository**:
   ```bash
   git clone https://github.com/yourusername/php8.4-plugin-framework.git
   ```

2. **Navigate to the project directory**:
   ```bash
   cd php8.4-plugin-framework
   ```

3. **Run the PHP built-in server**:
   ```bash
   php -S localhost:8000
   ```

4. **Open your browser and navigate to**:
   ```
   http://localhost:8000
   ```

5. **Extend the framework** by creating new plugin classes that extend the `Plugin` abstract class.

## License

This project is licensed under the AGPL-3.0 License. See the [LICENSE](LICENSE) file for details.

