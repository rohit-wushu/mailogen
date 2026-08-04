<?php

declare(strict_types=1);

final class AutomationController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $this->render('automations/index', [
            'workflows' => Workflow::withStepCounts($this->uid()),
        ], 'Automations');
    }

    public function edit(): void
    {
        $this->requireAuth();
        $id = int_input('id');
        $workflow = $id ? Workflow::findForUser($id, $this->uid()) : null;
        $this->render('automations/edit', [
            'workflow' => $workflow,
            'steps'    => $workflow ? WorkflowStep::forWorkflow((int) $workflow['id']) : [],
            'lists'    => ContactList::withCounts($this->uid()),
            'groups'   => SmtpGroup::withCounts($this->uid()),
        ], $workflow ? 'Edit Automation' : 'New Automation');
    }

    public function store(): void
    {
        $this->requireAuth();
        csrf_guard();
        $name = str_input('name');
        if ($name === '') {
            flash('error', 'Automation name is required.');
            $this->back('automations');
        }

        $data = [
            'name'          => $name,
            'list_id'       => int_input('list_id') ?: null,
            'smtp_group_id' => int_input('smtp_group_id') ?: null,
            'trigger_type'  => in_array(str_input('trigger_type'), ['manual', 'contact_added'], true) ? str_input('trigger_type') : 'manual',
        ];

        $id = int_input('id');
        if ($id && Workflow::findForUser($id, $this->uid())) {
            Workflow::update($id, $data);
        } else {
            $data['user_id'] = $this->uid();
            $data['status']  = 'draft';
            $id = Workflow::insert($data);
        }

        $this->saveSteps($id);
        flash('success', 'Automation saved.');
        redirect('automations/edit?id=' . $id);
    }

    private function saveSteps(int $workflowId): void
    {
        WorkflowStep::deleteForWorkflow($workflowId);
        $types = $_POST['step_type'] ?? [];
        if (!is_array($types)) {
            return;
        }
        $order = 1;
        foreach ($types as $i => $type) {
            $type = $type === 'wait' ? 'wait' : 'email';
            if ($type === 'wait') {
                WorkflowStep::insert([
                    'workflow_id' => $workflowId,
                    'step_order'  => $order++,
                    'step_type'   => 'wait',
                    'wait_days'   => max(0, (int) ($_POST['wait_days'][$i] ?? 0)),
                    'wait_hours'  => max(0, (int) ($_POST['wait_hours'][$i] ?? 0)),
                ]);
            } else {
                $subject = trim((string) ($_POST['email_subject'][$i] ?? ''));
                $body    = trim((string) ($_POST['email_body'][$i] ?? ''));
                if ($subject === '' && $body === '') {
                    continue;
                }
                WorkflowStep::insert([
                    'workflow_id'     => $workflowId,
                    'step_order'      => $order++,
                    'step_type'       => 'email',
                    'subject'         => $subject,
                    'body_html'       => $body,
                    'stop_if_opened'  => isset($_POST['stop_opened'][$i]) ? 1 : 0,
                    'stop_if_clicked' => isset($_POST['stop_clicked'][$i]) ? 1 : 0,
                    'stop_if_replied' => isset($_POST['stop_replied'][$i]) ? 1 : 0,
                    'stop_if_unsub'   => isset($_POST['stop_unsub'][$i]) ? 1 : 0,
                ]);
            }
        }
    }

    public function activate(): void
    {
        $this->requireAuth();
        csrf_guard();
        $workflow = Workflow::findForUser(int_input('id'), $this->uid());
        if ($workflow) {
            Workflow::update((int) $workflow['id'], ['status' => 'active']);
            $n = AutomationEngine::enrollList($workflow);
            flash('success', "Automation activated - {$n} contacts enrolled.");
        }
        redirect('automations');
    }

    public function pause(): void
    {
        $this->requireAuth();
        csrf_guard();
        $workflow = Workflow::findForUser(int_input('id'), $this->uid());
        if ($workflow) {
            Workflow::update((int) $workflow['id'], ['status' => 'paused']);
            flash('success', 'Automation paused.');
        }
        redirect('automations');
    }

    public function delete(): void
    {
        $this->requireAuth();
        csrf_guard();
        Workflow::delete(int_input('id'), $this->uid());
        flash('success', 'Automation deleted.');
        redirect('automations');
    }
}
