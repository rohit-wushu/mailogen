<?php

declare(strict_types=1);

final class TeamController extends BaseController
{
    private const ROLE_LABELS = ['admin' => 'Admin', 'member' => 'Member'];

    /** Send an invite email to join this tenant's account. */
    public function invite(): void
    {
        $this->requireAuth();
        $this->requireTeamAdmin();
        csrf_guard();

        $email = strtolower(trim((string) input('email', '')));
        $role  = str_input('team_role') === 'admin' ? 'admin' : 'member';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Please enter a valid email.');
            $this->back('settings');
        }
        if (User::findByEmail($email)) {
            flash('error', 'That email already has an account.');
            $this->back('settings');
        }
        $already = array_filter(TeamInvite::pendingForOwner($this->uid()), static fn ($i) => $i['email'] === $email);
        if ($already !== []) {
            flash('error', 'There is already a pending invite for that email.');
            $this->back('settings');
        }

        $invite = TeamInvite::create($this->uid(), $email, $role, $this->actorId());
        $this->sendInviteEmail($invite);
        flash('success', "Invite sent to {$email}.");
        $this->back('settings');
    }

    public function cancelInvite(): void
    {
        $this->requireAuth();
        $this->requireTeamAdmin();
        csrf_guard();
        $id = int_input('id');
        $invite = TeamInvite::find($id);
        if ($invite && (int) $invite['owner_id'] === $this->uid()) {
            TeamInvite::delete($id);
            flash('success', 'Invite cancelled.');
        }
        $this->back('settings');
    }

    public function updateRole(): void
    {
        $this->requireAuth();
        $this->requireTeamAdmin();
        csrf_guard();
        $id = int_input('id');
        $role = str_input('team_role') === 'admin' ? 'admin' : 'member';
        $member = User::find($id);
        if ($member && (int) ($member['owner_id'] ?? 0) === $this->uid()) {
            User::update($id, ['team_role' => $role]);
            flash('success', 'Role updated.');
        }
        $this->back('settings');
    }

    public function remove(): void
    {
        $this->requireAuth();
        $this->requireTeamAdmin();
        csrf_guard();
        $id = int_input('id');
        $member = User::find($id);
        if ($member && (int) ($member['owner_id'] ?? 0) === $this->uid()) {
            User::delete($id);
            flash('success', 'Team member removed.');
        }
        $this->back('settings');
    }

    /** Public: the invite-acceptance page — GET shows the form, POST creates the account. */
    public function accept(): void
    {
        if (Auth::check()) {
            redirect('dashboard');
        }
        $token = str_input('token');
        $invite = $token ? TeamInvite::findByToken($token) : null;
        if (!$invite) {
            flash('error', 'This invite link is invalid or has expired.');
            redirect('login');
        }

        if ($this->isPost()) {
            csrf_guard();
            if (User::findByEmail($invite['email'])) {
                flash('error', 'That email already has an account. Please log in instead.');
                redirect('login');
            }
            $name = str_input('name');
            $pass = (string) input('password', '');
            $errors = [];
            if (mb_strlen($name) < 1) $errors[] = 'Please enter your name.';
            if (strlen($pass) < 8)    $errors[] = 'Password must be at least 8 characters.';

            if ($errors === []) {
                $id = User::createTeamMember((int) $invite['owner_id'], $name, $invite['email'], $pass, $invite['team_role']);
                TeamInvite::markAccepted((int) $invite['id']);
                session_regenerate_id(true);
                $_SESSION['user_id'] = $id;
                flash('success', 'Welcome to the team!');
                redirect('dashboard');
            }
            flash('error', implode(' ', $errors));
        }

        $owner = User::find((int) $invite['owner_id']);
        view_bare('auth/accept_invite', [
            'invite' => $invite,
            'owner'  => $owner,
        ], 'Join ' . (($owner['company'] ?? '') ?: ($owner['name'] ?? APP_NAME)));
    }

    private function sendInviteEmail(array $invite): void
    {
        $owner = User::find((int) $invite['owner_id']);
        $inviter = User::find($this->actorId());
        $link = url('team/accept?token=' . $invite['token']);
        $roleLabel = self::ROLE_LABELS[$invite['team_role']] ?? 'Member';
        $orgName = $owner['company'] ?: $owner['name'] ?? APP_NAME;
        $html = '<p>Hi,</p>'
              . '<p>' . e($inviter['name'] ?? '') . ' invited you to join <strong>' . e($orgName) . '</strong> on '
              . APP_NAME . ' as a <strong>' . e($roleLabel) . '</strong>.</p>'
              . '<p><a href="' . $link . '">' . $link . '</a></p>'
              . '<p>This link expires in 7 days.</p>';
        Mailer::sendSystem($invite['email'], "You're invited to join {$orgName} on " . APP_NAME, $html, $this->uid());
    }
}
