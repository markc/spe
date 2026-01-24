<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Plugins\Contact;

use SPE\Session\Core\Plugin;

final class ContactModel extends Plugin
{
    #[\Override]
    public function list(): array
    {
        $_SESSION['visit_count'] = ($_SESSION['visit_count'] ?? 0) + 1;
        $_SESSION['last_page'] = 'Contact';

        return [
            'head' => 'Contact Page',
            'main' => 'Get in touch using the <b>email form</b> below.',
            'draft_subject' => $_SESSION['draft_subject'] ?? '',
            'draft_message' => $_SESSION['draft_message'] ?? '',
        ];
    }

    public function save(): array
    {
        $_SESSION['draft_subject'] = $_POST['subject'] ?? '';
        $_SESSION['draft_message'] = $_POST['message'] ?? '';
        $this->ctx->flash('msg', 'Draft saved to session!');
        $this->ctx->flash('type', 'success');

        return $this->list();
    }

    public function clear(): array
    {
        unset($_SESSION['draft_subject'], $_SESSION['draft_message']);
        $this->ctx->flash('msg', 'Draft cleared from session!');
        $this->ctx->flash('type', 'warning');

        return $this->list();
    }
}
