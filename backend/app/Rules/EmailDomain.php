<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Class EmailDomain
 *
 * Custom validation rule to validate that an email address uses an allowed domain.
 *
 * Usage:
 *   new EmailDomain(['gmail.com', 'yahoo.com'])
 *   new EmailDomain(['company.com'], true) // Allow subdomains
 */
class EmailDomain implements ValidationRule
{
    /**
     * The list of allowed domains.
     */
    protected array $allowedDomains;

    /**
     * Whether to allow subdomains.
     */
    protected bool $allowSubdomains;

    /**
     * Custom error message.
     */
    protected ?string $message = null;

    /**
     * Create a new rule instance.
     *
     * @param array $allowedDomains List of allowed email domains
     * @param bool $allowSubdomains Whether to allow subdomains (default: false)
     */
    public function __construct(
        array $allowedDomains,
        bool $allowSubdomains = false
    ) {
        $this->allowedDomains = array_map('strtolower', $allowedDomains);
        $this->allowSubdomains = $allowSubdomains;
    }

    /**
     * Set a custom error message.
     *
     * @param string $message
     * @return self
     */
    public function withMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  string $attribute
     * @param  mixed $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        // Extract domain from email
        $emailParts = explode('@', $value);

        if (count($emailParts) !== 2) {
            $fail($this->message ?? 'The email address must be from an allowed domain.');
            return;
        }

        $domain = strtolower($emailParts[1]);

        // Check if domain is allowed
        if ($this->allowSubdomains) {
            // Allow subdomains (e.g., mail.company.com if company.com is allowed)
            $isAllowed = collect($this->allowedDomains)->contains(function ($allowedDomain) use ($domain) {
                return $domain === $allowedDomain || str_ends_with($domain, '.' . $allowedDomain);
            });
        } else {
            // Exact domain match only
            $isAllowed = in_array($domain, $this->allowedDomains, true);
        }

        if (!$isAllowed) {
            $domains = implode(', ', $this->allowedDomains);
            $fail($this->message ?? "The email address must be from one of the following domains: {$domains}.");
        }
    }
}
