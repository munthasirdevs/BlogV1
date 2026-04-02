import { cn } from '@/utils';
import { Check, X } from 'lucide-react';

/**
 * Password strength indicator component
 * @param {Object} props - Component props
 * @param {string} props.password - Password to check
 */
function PasswordStrength({ password = '' }) {
  const checks = {
    minLength: password.length >= 8,
    hasUppercase: /[A-Z]/.test(password),
    hasLowercase: /[a-z]/.test(password),
    hasNumber: /[0-9]/.test(password),
    hasSpecialChar: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password),
  };

  const passedChecks = Object.values(checks).filter(Boolean).length;
  const totalChecks = Object.keys(checks).length;

  // Calculate strength
  let strength = 'weak';
  let strengthColor = 'bg-danger-500';
  let strengthWidth = 'w-1/3';

  if (passedChecks === totalChecks) {
    strength = 'strong';
    strengthColor = 'bg-success-500';
    strengthWidth = 'w-full';
  } else if (passedChecks >= 3) {
    strength = 'medium';
    strengthColor = 'bg-warning-500';
    strengthWidth = 'w-2/3';
  } else if (passedChecks >= 1) {
    strengthWidth = 'w-1/3';
  } else {
    strengthWidth = 'w-0';
  }

  const requirements = [
    { key: 'minLength', label: 'At least 8 characters' },
    { key: 'hasUppercase', label: 'Uppercase letter' },
    { key: 'hasLowercase', label: 'Lowercase letter' },
    { key: 'hasNumber', label: 'Number' },
    { key: 'hasSpecialChar', label: 'Special character' },
  ];

  return (
    <div className="space-y-3">
      {/* Strength Meter */}
      {password.length > 0 && (
        <div className="space-y-1">
          <div className="flex justify-between text-xs">
            <span className="text-secondary-600 dark:text-secondary-400">Password strength</span>
            <span
              className={cn(
                'font-medium capitalize',
                strength === 'strong' && 'text-success-600 dark:text-success-400',
                strength === 'medium' && 'text-warning-600 dark:text-warning-400',
                strength === 'weak' && 'text-danger-600 dark:text-danger-400'
              )}
            >
              {strength}
            </span>
          </div>
          <div className="h-1.5 bg-secondary-200 dark:bg-secondary-700 rounded-full overflow-hidden">
            <div
              className={cn('h-full transition-all duration-300', strengthColor)}
              style={{ width: `${(passedChecks / totalChecks) * 100}%` }}
            />
          </div>
        </div>
      )}

      {/* Requirements Checklist */}
      <div className="grid grid-cols-1 gap-1.5">
        {requirements.map((req) => (
          <div
            key={req.key}
            className={cn(
              'flex items-center gap-2 text-xs transition-colors',
              checks[req.key]
                ? 'text-success-600 dark:text-success-400'
                : 'text-secondary-500 dark:text-secondary-400'
            )}
          >
            {checks[req.key] ? (
              <Check className="w-3.5 h-3.5" />
            ) : (
              <X className="w-3.5 h-3.5" />
            )}
            <span>{req.label}</span>
          </div>
        ))}
      </div>
    </div>
  );
}

export default PasswordStrength;
