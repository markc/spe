<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Htmx\Plugins\Contact;

use SPE\Htmx\Core\Plugin;

final class ContactModel extends Plugin
{
    #[\Override]
    public function list(): array
    {
        return [
            'head' => 'Contact Page',
            'main' => <<<HTML
            <p>This is an ultra simple single-file PHP8 plus custom CSS framework and template system example. Comments and pull requests are most welcome via the Issue Tracker link.</p>
            <form method="post" onsubmit="return handleContact(this);">
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" required>
                </div>
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" rows="4" required></textarea>
                </div>
                <div class="text-right">
                    <button type="submit" class="btn">Send</button>
                </div>
            </form>
            <script>function handleContact(f){location.href='mailto:{$this->ctx->email}?subject='+encodeURIComponent(f.subject.value)+'&body='+encodeURIComponent(f.message.value);showToast('Opening email client...','success');return false;}</script>
            <div class="btn-group-center mt-2">
                <a href="https://github.com/markc/spe" class="btn">SPE Project Page</a>
                <a href="https://github.com/markc/spe/issues" class="btn">SPE Issue Tracker</a>
            </div>
            HTML,
            'foot' => __METHOD__ . ' (action)<br>Using the ' . $this->ctx->in['t'] . ' theme',
        ];
    }
}
