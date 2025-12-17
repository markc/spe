<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Autoload\Plugins\Contact;

use SPE\Autoload\Core\View;

final readonly class ContactView extends View {
    #[\Override] public function list(): string {
        $e = $this->ctx->ary['email'] ?? $this->ctx->email;
        return <<<HTML
        <div class="card"><h2>{$this->ctx->ary['head']}</h2>
        <form onsubmit="return handleContact(this)">
            <div class="form-group"><label>Subject</label><input type="text" id="subject" required></div>
            <div class="form-group"><label>Message</label><textarea id="message" rows="4" required></textarea></div>
            <button class="btn w-full">Send</button>
        </form></div>
        <script>function handleContact(f){location.href='mailto:$e?subject='+encodeURIComponent(f.subject.value)+'&body='+encodeURIComponent(f.message.value);showToast('Opening...','success');return false;}</script>
        HTML;
    }
}
