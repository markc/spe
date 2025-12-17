<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Plugins\Profile;

use SPE\App\{Db, QueryType, Util};
use SPE\Blog\Core\{Ctx, Plugin};

final class ProfileModel extends Plugin {
    private ?Db $dbh = null;

    public function __construct(protected Ctx $ctx) {
        parent::__construct($ctx);
        $this->dbh = new Db('users');
    }

    // View/edit profile form
    #[\Override] public function list(): array {
        if (!Util::is_usr()) {
            Util::redirect('?o=Auth');
        }

        $id = $_SESSION['usr']['id'];
        $usr = $this->dbh->read('users', '*', 'id = :id', ['id' => $id], QueryType::One);

        if (!Util::is_post()) {
            return ['action' => 'profile', 'usr' => $usr];
        }

        // Update profile
        $fname = trim($_POST['fname'] ?? '');
        $lname = trim($_POST['lname'] ?? '');
        $altemail = trim($_POST['altemail'] ?? '');

        // Validate alternate email if provided
        if ($altemail && !filter_var($altemail, FILTER_VALIDATE_EMAIL)) {
            Util::log('Invalid alternate email address');
            return ['action' => 'profile', 'usr' => array_merge($usr, [
                'fname' => $fname, 'lname' => $lname, 'altemail' => $altemail
            ])];
        }

        $this->dbh->update('users', [
            'fname' => $fname,
            'lname' => $lname,
            'altemail' => $altemail,
            'updated' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $id]);

        // Update session
        $_SESSION['usr']['fname'] = $fname;
        $_SESSION['usr']['lname'] = $lname;

        Util::log('Profile updated', 'success');
        Util::redirect('?o=Profile');
    }

    // Change password (redirect to Auth)
    #[\Override] public function update(): array {
        Util::redirect('?o=Auth&m=update');
    }

    #[\Override] public function create(): array { return []; }
    #[\Override] public function read(): array { return []; }
    #[\Override] public function delete(): array { return []; }
}
