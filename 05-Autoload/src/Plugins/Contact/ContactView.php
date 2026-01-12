<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Autoload\Plugins\Contact;

use SPE\Autoload\Core\View;

final class ContactView extends View
{
    #[\Override]
    public function list(): string
    {
        return <<<HTML
        <div class="card">
            <h2>{$this->ary['head']}</h2>
            <p>{$this->ary['main']}</p>
            <form class="mt-2" onsubmit="return handleContact(this)">
                <div class="form-group"><label for="subject">Subject</label><input type="text" id="subject" name="subject" required></div>
                <div class="form-group"><label for="message">Message</label><textarea id="message" name="message" rows="4" required></textarea></div>
                <div class="text-right"><button type="submit" class="btn">Send Message</button></div>
            </form>
        </div>
        <script>
        function handleContact(form) {
            location.href = 'mailto:{$this->ctx->email}?subject=' + encodeURIComponent(form.subject.value) + '&body=' + encodeURIComponent(form.message.value);
            showToast('Opening email client...', 'success');
            return false;
        }
        </script>
        HTML;
    }
}
