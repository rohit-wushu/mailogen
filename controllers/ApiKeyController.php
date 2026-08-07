<?php

declare(strict_types=1);

final class ApiKeyController extends BaseController
{
    public function store(): void
    {
        $this->requireAuth();
        $this->requireTeamAdmin();
        csrf_guard();

        $label = str_input('label') ?: 'API key';
        [, $rawKey] = ApiKey::generate($this->uid(), $this->actorId(), $label);

        // Shown exactly once — stash in a flash-like session slot the settings
        // view reads and clears on render (a plain flash() would truncate the
        // long key when re-rendered through the usual one-line success banner).
        $_SESSION['_new_api_key'] = $rawKey;
        flash('success', 'API key created — copy it now, you won\'t see it again.');
        $this->back('settings');
    }

    public function revoke(): void
    {
        $this->requireAuth();
        $this->requireTeamAdmin();
        csrf_guard();
        $id = int_input('id');
        $key = ApiKey::find($id);
        if ($key && (int) $key['user_id'] === $this->uid()) {
            ApiKey::delete($id);
            flash('success', 'API key revoked.');
        }
        $this->back('settings');
    }
}
