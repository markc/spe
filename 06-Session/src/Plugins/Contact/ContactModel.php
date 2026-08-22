<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Plugins\Contact;

use SPE\Session\Core\{Flash, Plugin};

final class ContactModel extends Plugin
{
    #[\Override]
    public function list(): array
    {
        return ['title' => 'Contact', 'body' => 'Send a message. The form posts to the server, which checks the CSRF token before accepting it.'];
    }

    #[\Override]
    public function create(): array
    {
        if ($p = $this->ctx->post()) {
            $subject = trim((string) ($p['subject'] ?? ''));
            if ($subject === '') {
                $this->ctx->flash(Flash::Warning, 'Please enter a subject.');
            } else {
                // A real app would send mail here; this chapter is about the request cycle.
                $this->ctx->flash(Flash::Success, "Thanks — your message about \"$subject\" was received.");
            }
        }
        $this->redirect('?o=Contact');
    }
}
