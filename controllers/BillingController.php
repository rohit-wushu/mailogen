<?php

declare(strict_types=1);

final class BillingController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $this->render('billing/index', [
            'plans'      => Plan::active(),
            'current'    => Billing::effectivePlan($this->user),
            'active'     => Billing::isActive($this->user),
            'usage'      => Billing::usage($this->user),
            'payments'   => Payment::forUser($this->uid()),
            'pendingReq' => PlanRequest::pendingForUser($this->uid()),
        ], 'Billing & Plans');
    }

    /**
     * User subscribes to a plan. Free plans switch instantly; paid plans create
     * a PENDING request that an admin must approve (manual approval flow).
     */
    public function request(): void
    {
        $this->requireAuth();
        csrf_guard();

        $plan = Plan::find(int_input('plan_id'));
        if (!$plan || (int) $plan['is_active'] !== 1) {
            flash('error', 'That plan is not available.');
            redirect('billing');
        }
        $months = max(1, min(12, int_input('months', 1)));

        // Free plan — apply immediately, no approval needed.
        if ((float) $plan['price_monthly'] <= 0) {
            Billing::activate($this->uid(), (int) $plan['id'], 0);
            flash('success', 'You are now on the ' . $plan['name'] . ' plan.');
            redirect('billing');
        }

        // Already on this exact plan and active.
        if ((int) ($this->user['plan_id'] ?? 0) === (int) $plan['id'] && Billing::isActive($this->user)) {
            flash('info', 'You are already on the ' . $plan['name'] . ' plan.');
            redirect('billing');
        }
        // Don't allow stacking requests.
        if (PlanRequest::pendingForUser($this->uid())) {
            flash('info', 'You already have a subscription request awaiting approval.');
            redirect('billing');
        }

        PlanRequest::insert([
            'user_id'       => $this->uid(),
            'plan_id'       => (int) $plan['id'],
            'period_months' => $months,
            'amount'        => (float) $plan['price_monthly'] * $months,
            'currency'      => BILLING_CURRENCY,
            'status'        => 'pending',
        ]);
        SystemLog::write('info', 'billing', "Subscription request for {$plan['name']} — awaiting admin approval", $this->uid());

        flash('success', 'Your request for the ' . $plan['name'] . ' plan has been sent to the admin for approval. You will be upgraded once it is approved.');
        redirect('billing');
    }

    /**
     * Begin an upgrade. In DEMO mode (no gateway keys) the plan activates
     * instantly. In live mode this creates a Razorpay order and returns the
     * details the client-side checkout needs.
     */
    public function checkout(): void
    {
        $this->requireAuth();
        csrf_guard();

        $plan = Plan::find(int_input('plan_id'));
        $months = max(1, min(12, int_input('months', 1)));
        if (!$plan) {
            json_response(['ok' => false, 'error' => 'Unknown plan.'], 404);
        }
        if ((float) $plan['price_monthly'] <= 0) {
            // Free plan — just switch to it.
            $this->activate((int) $plan['id'], 0, 'manual', null);
            json_response(['ok' => true, 'demo' => true, 'message' => 'You are on the ' . $plan['name'] . ' plan.']);
        }

        $amount = (float) $plan['price_monthly'] * $months;

        // DEMO mode: record a paid payment and activate immediately.
        if (!Razorpay::isConfigured()) {
            $payId = Payment::insert([
                'user_id' => $this->uid(), 'plan_id' => (int) $plan['id'], 'amount' => $amount,
                'currency' => BILLING_CURRENCY, 'period_months' => $months, 'gateway' => 'demo',
                'status' => 'paid', 'paid_at' => date('Y-m-d H:i:s'),
            ]);
            $this->activate((int) $plan['id'], $months, 'demo', 'demo#' . $payId);
            json_response(['ok' => true, 'demo' => true, 'message' => "Demo upgrade to {$plan['name']} activated."]);
        }

        // Live mode: create gateway order.
        try {
            $order = Razorpay::createOrder($amount, 'plan_' . $plan['id'] . '_u' . $this->uid());
        } catch (\Throwable $e) {
            json_response(['ok' => false, 'error' => $e->getMessage()], 502);
        }
        Payment::insert([
            'user_id' => $this->uid(), 'plan_id' => (int) $plan['id'], 'amount' => $amount,
            'currency' => BILLING_CURRENCY, 'period_months' => $months, 'gateway' => 'razorpay',
            'gateway_order_id' => $order['id'], 'status' => 'created',
        ]);
        json_response([
            'ok'       => true,
            'demo'     => false,
            'key'      => RAZORPAY_KEY_ID,
            'order_id' => $order['id'],
            'amount'   => $order['amount'],
            'currency' => $order['currency'],
            'name'     => APP_NAME,
            'plan'     => $plan['name'],
            'prefill'  => ['name' => $this->user['name'], 'email' => $this->user['email']],
        ]);
    }

    /** Razorpay checkout success callback — verify signature then activate. */
    public function callback(): void
    {
        $this->requireAuth();
        csrf_guard();

        $orderId   = str_input('razorpay_order_id');
        $paymentId = str_input('razorpay_payment_id');
        $signature = str_input('razorpay_signature');

        $payment = Payment::findByOrder($orderId);
        if (!$payment || (int) $payment['user_id'] !== $this->uid()) {
            json_response(['ok' => false, 'error' => 'Payment record not found.'], 404);
        }
        if (!Razorpay::verifySignature($orderId, $paymentId, $signature)) {
            Payment::update((int) $payment['id'], ['status' => 'failed']);
            json_response(['ok' => false, 'error' => 'Payment verification failed.'], 400);
        }
        Payment::update((int) $payment['id'], [
            'status' => 'paid', 'gateway_payment_id' => $paymentId, 'paid_at' => date('Y-m-d H:i:s'),
        ]);
        $this->activate((int) $payment['plan_id'], (int) $payment['period_months'], 'razorpay', $paymentId);
        json_response(['ok' => true, 'message' => 'Payment successful — your plan is active.']);
    }

    /**
     * Apply a plan to the current user. Paid plans extend from the later of now
     * or the existing expiry (so renewals stack). $months = 0 → free/no expiry.
     */
    private function activate(int $planId, int $months, string $gateway, ?string $payId): void
    {
        $expires = null;
        if ($months > 0) {
            $base = $this->user['plan_expires_at'] && strtotime($this->user['plan_expires_at']) > time()
                ? strtotime($this->user['plan_expires_at'])
                : time();
            $expires = date('Y-m-d H:i:s', strtotime("+{$months} months", $base));
        }
        User::update($this->uid(), ['plan_id' => $planId, 'plan_expires_at' => $expires]);
        SystemLog::write('info', 'billing', "Plan #{$planId} activated via {$gateway}" . ($payId ? " ({$payId})" : ''), $this->uid());
    }
}
