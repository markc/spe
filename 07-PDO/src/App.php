<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\PDO;

use SPE\App\{Db, QueryType};

final class App {
    public string $buf = '';
    public array $a = [], $in, $out, $n1, $n2;

    public function __construct() {
        $i = ['l' => '', 'm' => 'list', 'o' => 'Home', 't' => 'Simple', 'x' => ''];
        $this->in = array_combine(array_keys($i), array_map(fn($k, $v) =>
            ($_REQUEST[$k] ?? $v) |> trim(...) |> htmlspecialchars(...), array_keys($i), $i));
        $this->out = ['doc' => 'SPE::07', 'head' => 'PDO PHP Example',
            'main' => 'Error: missing plugin!', 'foot' => '© 2015-2025 Mark Constable (MIT License)'];

        $db = new Db('blog');
        $this->n1 = array_map(fn($r) => [trim(($r['icon'] ?? '') . ' ' . $r['title']), ucfirst($r['slug'])],
            $db->read('posts', 'id,title,slug,icon', "type='page' ORDER BY id", [], QueryType::All));
        $this->n1[] = ['📝 Blog', 'Blog'];
        $this->n2 = [['🎨 Simple', 'Simple'], ['📐 TopNav', 'TopNav'], ['📑 SideBar', 'SideBar']];

        ['o' => $o, 'm' => $m, 't' => $t] = $this->in;
        $this->a = $o === 'Blog'
            ? (new Model($this))->$m()
            : ($db->read('posts', '*', "slug=:s AND type='page'", ['s' => strtolower($o)], QueryType::One) ?: []);
        $vm = $o === 'Blog' || !$this->a ? $m : 'page';
        $this->out['main'] = (new View($this))->$vm();
        $this->buf = (new Theme($this))->$t();
    }

    public function __toString(): string {
        return match ($this->in['x']) {
            'json' => (header('Content-Type: application/json') ?: '') . json_encode($this->out),
            default => $this->buf
        };
    }
}
