<?php

namespace Platform\Services;

/**
 * Platform-wide settings (pf_settings): general, billing rules, email
 * templates and payment gateways. Every value here is editable in the
 * admin panel; the arrays below are only first-run defaults.
 */
class PlatformSettings
{
    public const DEFAULTS = [
        'general' => [
            'platformName' => 'CheckA+',
            'rootDomain'   => 'checkaplus.co.za',
            'supportEmail' => '',
            'currency'     => 'ZAR',
            'vatRate'      => 15,
            'vatNumber'    => '',
            'companyName'  => '',
            'companyAddress' => '',
            'invoicePrefix'  => 'CP-',
        ],
        'billing' => [
            'remindBefore'        => '7,3',     // days before due date
            'remindOnDue'         => 1,
            'remindAfter'         => '3',       // days after due date
            'graceDays'           => 7,         // overdue days before suspension
            'autoSuspend'         => 1,
            'autoUnsuspend'       => 1,
            'retentionDays'       => 90,        // suspended/cancelled data kept this long
            'finalWarningDays'    => 7,         // warning before data deletion
            'trialDays'           => 14,
            'trialRemindBefore'   => '3,1',
            'trialEndAction'      => 'suspend', // suspend | downgrade
            'invoiceLeadDays'     => 7,         // invoice issued this many days before renewal
            'annualDiscountNote'  => '2 months free',
            'abandonDays'         => 7,         // unpaid sign-ups are removed after this many days
        ],
        'website' => [
            'billingNote'       => 'All prices exclude VAT. Switch to annual billing for 2 months free.',
            'enterpriseName'    => 'Enterprise',
            'enterpriseTagline' => 'Custom volumes, SSO and dedicated support for large fleets and dealer groups.',
            'enterpriseCta'     => 'Contact us',
            'addOns'            => "Extra user seat | R99/mo per user
VIN history check bundle — 10 checks | R450 once-off
Additional branch / location | R399/mo",
            'faq'               => "Is there a free trial?
Yes — every plan starts with a free trial. No card is required to begin.

Can I cancel at any time?
Yes. There are no long-term contracts. Cancel from your subscription page and your workspace stays active until the end of the paid period.

Who owns my inspection data?
You do. All inspection records, photos, reports and client data belong to your business.

What payment methods do you accept?
Card, Instant EFT and bank transfer.",
            'leadEmail'         => '',
        ],
        'mail' => [
            'transport' => 'mail',   // mail | smtp | brevo
            'fromEmail' => '',
            'fromName'  => 'CheckA+',
            'smtpHost'  => '', 'smtpUser' => '', 'smtpPass' => '', 'smtpPort' => 587, 'smtpCrypto' => 'tls',
            'brevoKey'  => '',
        ],
        'gateways' => [
            'paystack' => ['enabled' => 0, 'publicKey' => '', 'secretKey' => ''],
            'payfast'  => ['enabled' => 0, 'merchantId' => '', 'merchantKey' => '', 'passphrase' => '', 'sandbox' => 1],
            'ozow'     => ['enabled' => 0, 'siteCode' => '', 'privateKey' => '', 'apiKey' => '', 'sandbox' => 1],
            'eft'      => ['enabled' => 0, 'bankName' => '', 'accountName' => '', 'accountNumber' => '', 'branchCode' => '', 'accountType' => '', 'instructions' => 'Use your invoice number as the reference.'],
        ],
        'templates' => [
            'welcome'         => ['subject' => 'Welcome to {platform}, {company}', 'body' => "Hi {contact},\n\nYour {platform} workspace is ready at {workspace_url}.\n\nSign in with {email}. Your package: {package}.\n\nWelcome aboard!"],
            'trial_ending'    => ['subject' => 'Your {platform} trial ends on {trial_end}', 'body' => "Hi {contact},\n\nYour free trial for {company} ends on {trial_end}. Choose a package and add payment to keep going: {pay_url}"],
            'invoice'         => ['subject' => '{platform} invoice {invoice_no} — {amount}', 'body' => "Hi {contact},\n\nYour {platform} invoice {invoice_no} for {amount} is due on {due_date}.\n\nPay securely: {pay_url}"],
            'reminder_before' => ['subject' => 'Reminder: {amount} due on {due_date}', 'body' => "Hi {contact},\n\nA friendly reminder that {amount} for your {platform} subscription is due on {due_date}.\n\nPay now: {pay_url}"],
            'reminder_due'    => ['subject' => 'Your {platform} payment of {amount} is due today', 'body' => "Hi {contact},\n\nYour subscription payment of {amount} is due today.\n\nPay now: {pay_url}"],
            'reminder_after'  => ['subject' => 'Overdue: {amount} — workspace pauses on {suspend_date}', 'body' => "Hi {contact},\n\nYour payment of {amount} is {days_overdue} days overdue. To avoid {company}'s workspace being paused on {suspend_date}, please pay now: {pay_url}"],
            'suspended'       => ['subject' => '{company} workspace paused', 'body' => "Hi {contact},\n\nYour {platform} workspace has been paused because {amount} is unpaid. Nothing has been deleted — pay to reactivate instantly: {pay_url}"],
            'unsuspended'     => ['subject' => '{company} workspace is active again', 'body' => "Hi {contact},\n\nThanks for your payment — your workspace at {workspace_url} is active again."],
            'final_warning'   => ['subject' => 'Final notice: {company} data will be deleted on {delete_date}', 'body' => "Hi {contact},\n\nYour workspace has been inactive since {suspended_date}. All data will be permanently deleted on {delete_date}. Pay to keep it: {pay_url}"],
            'find_workspace'  => ['subject' => 'Your {platform} workspace', 'body' => "Hi,

You asked for your {platform} sign-in link. Your workspace(s):

{workspace_list}

If you didn't ask for this, you can ignore this email."],
            'receipt'         => ['subject' => 'Payment received — thank you', 'body' => "Hi {contact},\n\nWe received {amount} for invoice {invoice_no}. Thank you!"],
        ],
    ];

    public const TEMPLATE_LABELS = [
        'welcome' => 'Welcome (workspace ready)', 'trial_ending' => 'Trial ending', 'invoice' => 'New invoice',
        'reminder_before' => 'Reminder before due date', 'reminder_due' => 'Due today', 'reminder_after' => 'Overdue reminder',
        'suspended' => 'Workspace paused', 'unsuspended' => 'Workspace reactivated', 'final_warning' => 'Final warning before deletion',
        'receipt' => 'Payment receipt', 'find_workspace' => 'Find my workspace',
    ];

    public const PLACEHOLDERS = ['{platform}', '{company}', '{contact}', '{email}', '{package}', '{workspace_url}', '{amount}', '{invoice_no}',
        '{due_date}', '{pay_url}', '{trial_end}', '{days_overdue}', '{suspend_date}', '{suspended_date}', '{delete_date}', '{workspace_list}'];

    private static array $cache = [];

    /** A whole settings group, merged over its defaults. */
    public static function get(string $group): array
    {
        if (! isset(self::$cache[$group])) {
            $row   = db_connect('platform')->table('settings')->where('key', $group)->get()->getRowArray();
            $saved = $row ? (array) json_decode((string) $row['value'], true) : [];
            self::$cache[$group] = self::merge(self::DEFAULTS[$group] ?? [], $saved);
        }

        return self::$cache[$group];
    }

    public static function value(string $group, string $key, $default = null)
    {
        return self::get($group)[$key] ?? $default;
    }

    public static function set(string $group, array $values): void
    {
        $merged = self::merge(self::get($group), $values);
        $db     = db_connect('platform');
        $data   = ['key' => $group, 'value' => json_encode($merged), 'updated_at' => date('Y-m-d H:i:s')];
        $db->table('settings')->where('key', $group)->countAllResults() > 0
            ? $db->table('settings')->where('key', $group)->update($data)
            : $db->table('settings')->insert($data);
        self::$cache[$group] = $merged;
    }

    /** Recursive merge where saved values win but new default keys still appear. */
    private static function merge(array $defaults, array $saved): array
    {
        foreach ($saved as $k => $v) {
            $defaults[$k] = (is_array($v) && isset($defaults[$k]) && is_array($defaults[$k]) && array_keys($defaults[$k]) !== range(0, count($defaults[$k]) - 1))
                ? self::merge($defaults[$k], $v)
                : $v;
        }

        return $defaults;
    }

    /** "7,3" → [7, 3] (unique, positive, sorted high→low). */
    public static function days(string $csv): array
    {
        $out = array_unique(array_filter(array_map('intval', preg_split('/[\s,;]+/', $csv) ?: []), static fn ($n) => $n > 0));
        rsort($out);

        return array_values($out);
    }

    /** Fill {placeholders} in a template. */
    public static function render(string $text, array $vars): string
    {
        $map = [];
        foreach ($vars as $k => $v) {
            $map['{' . $k . '}'] = (string) $v;
        }

        return strtr($text, $map);
    }
}
