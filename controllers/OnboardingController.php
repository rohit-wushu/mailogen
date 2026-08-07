<?php

declare(strict_types=1);

final class OnboardingController extends BaseController
{
    private const SUBSCRIBER_RANGES = ['1-1000', '1000-5000', '5000-10000', '10000-50000', '50000-100000', '100000-500000', '500000-1000000', '1000000+'];
    private const REFERRAL_SOURCES  = ['google', 'facebook', 'twitter', 'linkedin', 'other'];

    public function step1(): void
    {
        Auth::require();
        if ($this->alreadyDone()) {
            redirect('dashboard');
        }

        $fieldLabels = [
            'company' => 'Company name', 'org_address' => 'Address', 'city' => 'City',
            'zip' => 'ZIP / postal code', 'state' => 'State', 'country' => 'Country', 'timezone' => 'Timezone',
        ];
        if ($this->isPost()) {
            csrf_guard();
            $data = [
                'company'     => str_input('company'),
                'org_address' => str_input('address_line'),
                'city'        => str_input('city'),
                'zip'         => str_input('zip'),
                'state'       => str_input('state'),
                'country'     => str_input('country'),
                'timezone'    => str_input('timezone') ?: 'Asia/Kolkata',
                'org_website' => str_input('website') ?: null,
            ];
            $errors = [];
            foreach ($fieldLabels as $key => $label) {
                if ($data[$key] === '') {
                    $errors[$key] = "Please enter your {$label}.";
                }
            }
            if ($errors === []) {
                User::update($this->uid(), $data);
                redirect('onboarding/step2');
            }
            view_bare('onboarding/step1', [
                'countries' => Countries::names(),
                'timezones' => ['Asia/Kolkata', 'UTC', 'America/New_York', 'America/Chicago', 'America/Los_Angeles', 'Europe/London', 'Europe/Paris', 'Asia/Dubai', 'Asia/Singapore', 'Australia/Sydney'],
                'errors'    => $errors,
                'old'       => $_POST,
            ], 'Add Some Details');
            return;
        }

        view_bare('onboarding/step1', [
            'countries' => Countries::names(),
            'timezones' => ['Asia/Kolkata', 'UTC', 'America/New_York', 'America/Chicago', 'America/Los_Angeles', 'Europe/London', 'Europe/Paris', 'Asia/Dubai', 'Asia/Singapore', 'Australia/Sydney'],
            'errors'    => [],
            'old'       => [],
        ], 'Add Some Details');
    }

    public function step2(): void
    {
        Auth::require();
        if ($this->alreadyDone()) {
            redirect('dashboard');
        }
        if (empty($this->user['org_address'])) {
            redirect('onboarding/step1');
        }

        if ($this->isPost()) {
            csrf_guard();
            $range = str_input('subscriber_range');
            $collection = str_input('collection_method');
            $content = str_input('content_type');
            $errors = [];
            if (!in_array($range, self::SUBSCRIBER_RANGES, true)) $errors['subscriber_range'] = 'Please choose a subscriber range.';
            if ($collection === '')                               $errors['collection_method'] = 'Please tell us how you collect subscribers.';
            if ($content === '')                                  $errors['content_type'] = 'Please tell us what content you plan to send.';

            if ($errors === []) {
                $this->mergeOnboardingData([
                    'subscriber_range'  => $range,
                    'collection_method' => $collection,
                    'content_type'      => $content,
                ]);
                redirect('onboarding/step3');
            }
            view_bare('onboarding/step2', [
                'ranges'  => self::SUBSCRIBER_RANGES,
                'answers' => array_merge($this->onboardingData(), $_POST),
                'errors'  => $errors,
            ], 'Tell Us About Your Business');
            return;
        }

        view_bare('onboarding/step2', [
            'ranges'  => self::SUBSCRIBER_RANGES,
            'answers' => $this->onboardingData(),
            'errors'  => [],
        ], 'Tell Us About Your Business');
    }

    public function step3(): void
    {
        Auth::require();
        if ($this->alreadyDone()) {
            redirect('dashboard');
        }
        if (empty($this->user['org_address'])) {
            redirect('onboarding/step1');
        }

        if ($this->isPost()) {
            csrf_guard();
            $priorTool = str_input('prior_tool');
            $referral = str_input('referral_source');
            if (!in_array($priorTool, ['yes', 'no'], true) || !in_array($referral, self::REFERRAL_SOURCES, true)) {
                flash('error', 'Please answer both questions to continue.');
                redirect('onboarding/step3');
            }
            $this->mergeOnboardingData([
                'prior_tool'      => $priorTool,
                'referral_source' => $referral,
            ]);
            redirect('onboarding/step4');
        }

        view_bare('onboarding/step3', [
            'sources' => self::REFERRAL_SOURCES,
            'answers' => $this->onboardingData(),
        ], 'What Tools Do You Use');
    }

    public function step4(): void
    {
        Auth::require();
        if ($this->alreadyDone()) {
            redirect('dashboard');
        }
        if (empty($this->user['org_address'])) {
            redirect('onboarding/step1');
        }

        if ($this->isPost()) {
            csrf_guard();
            $mode = str_input('sending_mode') === 'domain' ? 'domain' : 'smtp';
            User::update($this->uid(), [
                'sending_mode'             => $mode,
                'onboarding_completed_at'  => date('Y-m-d H:i:s'),
            ]);
            flash('success', 'You\'re all set — welcome to ' . APP_NAME . '!');
            redirect('dashboard');
        }

        $cheapest = null;
        foreach (Plan::active() as $p) {
            if ((float) $p['price_smtp'] > 0 || (float) $p['price_domain'] > 0) {
                $cheapest = $p;
                break;
            }
        }
        $free = Billing::freePlan();
        view_bare('onboarding/step4', [
            'priceSmtp'          => $cheapest ? (float) $cheapest['price_smtp'] : 0,
            'priceDomain'        => $cheapest ? (float) $cheapest['price_domain'] : 0,
            'modeSmtpFeatures'   => Plan::modeCompareFeatures('smtp'),
            'modeDomainFeatures' => Plan::modeCompareFeatures('domain'),
            'freePlanName'       => $free['name'] ?? 'Free',
            'freeFeatures'       => Plan::featureList($free),
            'currentMode'        => $this->user['sending_mode'] ?? 'smtp',
        ], 'How Will You Send');
    }

    private function alreadyDone(): bool
    {
        return $this->user !== null && (
            $this->user['role'] === 'admin' || !empty($this->user['onboarding_completed_at'])
        );
    }

    private function onboardingData(): array
    {
        $raw = $this->user['onboarding_data'] ?? null;
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($decoded) ? $decoded : [];
    }

    private function mergeOnboardingData(array $new): void
    {
        $merged = array_merge($this->onboardingData(), $new);
        User::update($this->uid(), ['onboarding_data' => json_encode($merged)]);
    }
}
