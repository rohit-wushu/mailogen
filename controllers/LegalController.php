<?php

declare(strict_types=1);

/**
 * Public legal pages (no auth). Starter templates — have a lawyer review
 * before relying on them commercially.
 */
final class LegalController extends BaseController
{
    public function terms(): void
    {
        $this->page('terms', 'Terms of Service');
    }

    public function privacy(): void
    {
        $this->page('privacy', 'Privacy Policy');
    }

    public function acceptableUse(): void
    {
        $this->page('aup', 'Acceptable Use Policy');
    }

    private function page(string $doc, string $title): void
    {
        view_bare('legal/page', [
            'doc'     => $doc,
            'docTitle' => $title,
        ], $title);
    }
}
