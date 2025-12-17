<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\PDO\Plugins\Contact;

use SPE\PDO\Core\Ctx;

final class ContactView {
    public function __construct(private Ctx $ctx) {}

    public function list(): string {
        $email = $this->ctx->ary['email'] ?? $this->ctx->email;
        return <<<HTML
        <div class="card"><h2>{$this->ctx->ary['head']}</h2>
        <form onsubmit="return handleContact(this)">
            <div class="form-group"><label>Subject</label><input type="text" id="subject" required></div>
            <div class="form-group"><label>Message</label><textarea id="message" rows="4" required></textarea></div>
            <div class="text-right"><button class="btn">Send</button></div>
        </form></div>
        <script>function handleContact(f){location.href='mailto:$email?subject='+encodeURIComponent(f.subject.value)+'&body='+encodeURIComponent(f.message.value);showToast('Opening...','success');return false;}</script>
        HTML;
    }
}
