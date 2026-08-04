<?php

declare(strict_types=1);

final class TemplateController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $this->render('templates/index', [
            'templates' => Template::allForUser($this->uid(), 'updated_at DESC'),
        ], 'Templates');
    }

    public function edit(): void
    {
        $this->requireAuth();
        $id = int_input('id');
        $template = $id ? Template::findForUser($id, $this->uid()) : null;
        $this->render('templates/edit', [
            'template' => $template,
        ], $template ? 'Edit Template' : 'New Template');
    }

    public function store(): void
    {
        $this->requireAuth();
        csrf_guard();
        $name = str_input('name');
        $subject = str_input('subject');
        $body = (string) input('body_html', '');

        if ($name === '' || $subject === '') {
            flash('error', 'Template name and subject are required.');
            $this->back('templates');
        }

        $id = int_input('id');
        if ($id && Template::findForUser($id, $this->uid())) {
            Template::update($id, ['name' => $name, 'subject' => $subject, 'body_html' => $body]);
            flash('success', 'Template updated.');
        } else {
            Template::insert([
                'user_id'   => $this->uid(),
                'name'      => $name,
                'subject'   => $subject,
                'body_html' => $body,
            ]);
            flash('success', 'Template created.');
        }
        redirect('templates');
    }

    public function delete(): void
    {
        $this->requireAuth();
        csrf_guard();
        Template::delete(int_input('id'), $this->uid());
        flash('success', 'Template deleted.');
        redirect('templates');
    }

    /** Live HTML preview (used inside an iframe in the builder). */
    public function preview(): void
    {
        $this->requireAuth();
        $id = int_input('id');
        $template = $id ? Template::findForUser($id, $this->uid()) : null;
        header('Content-Type: text/html; charset=UTF-8');
        echo $template ? $template['body_html'] : '<p style="font-family:sans-serif;color:#888">No content yet.</p>';
        exit;
    }
}
