<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Plugins\Contact;

use SPE\Session\Core\View;

final class ContactView extends View
{
    #[\Override]
    public function list(): string
    {
        $subject = htmlspecialchars($this->ary['draft_subject']);
        $message = htmlspecialchars($this->ary['draft_message']);
        return <<<HTML
<div class="card">
    <h2>{$this->ary['head']}</h2>
    <p>{$this->ary['main']}</p>

    <h3>Session-Backed Form</h3>
    <p class="text-muted">Your draft is saved to the session. Navigate away and return - your text persists!</p>

    <form method="post" action="?o=Contact&m=save" class="mt-2">
        <div class="form-group">
            <label for="subject">Subject</label>
            <input type="text" id="subject" name="subject" value="{$subject}" placeholder="Type something and save...">
        </div>
        <div class="form-group">
            <label for="message">Message</label>
            <textarea id="message" name="message" rows="4" placeholder="Your message here...">{$message}</textarea>
        </div>
        <div class="flex justify-between">
            <div class="flex gap-2">
                <button type="submit" class="btn">💾 Save Draft</button>
                <a href="?o=Contact&m=clear" class="btn btn-warning">🗑️ Clear Draft</a>
            </div>
            <button type="button" class="btn btn-success" onclick="handleContact(this.form)">📧 Send Email</button>
        </div>
    </form>
</div>
<script>
function handleContact(form) {
    location.href = 'mailto:{$this->ctx->email}?subject=' + encodeURIComponent(form.subject.value) + '&body=' + encodeURIComponent(form.message.value);
    showToast('Opening email client...', 'success');
}
</script>
HTML;
    }

    public function save(): string { return $this->list(); }
    public function clear(): string { return $this->list(); }
}
