<?php

declare(strict_types=1);

final class ErrorController extends BaseController
{
    public function notFound(): void
    {
        http_response_code(404);
        if (Auth::check()) {
            $this->render('errors/404', [], 'Page not found');
        } else {
            view_bare('errors/404_public', [], 'Page not found');
        }
    }
}
