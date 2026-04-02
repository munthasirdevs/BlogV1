import { z } from 'zod';

/**
 * Password strength requirements
 */
export const passwordRequirements = {
  minLength: 8,
  requiresUppercase: true,
  requiresLowercase: true,
  requiresNumber: true,
  requiresSpecialChar: true,
};

/**
 * Check password strength
 * @param {string} password - Password to check
 * @returns {Object} Password strength result
 */
export function checkPasswordStrength(password) {
  const checks = {
    minLength: password.length >= passwordRequirements.minLength,
    hasUppercase: /[A-Z]/.test(password),
    hasLowercase: /[a-z]/.test(password),
    hasNumber: /[0-9]/.test(password),
    hasSpecialChar: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password),
  };

  const passedChecks = Object.values(checks).filter(Boolean).length;
  const totalChecks = Object.keys(checks).length;

  let strength = 'weak';
  if (passedChecks === totalChecks) {
    strength = 'strong';
  } else if (passedChecks >= 3) {
    strength = 'medium';
  }

  return {
    strength,
    score: passedChecks,
    total: totalChecks,
    checks,
  };
}

/**
 * Login validation schema
 */
export const loginSchema = z.object({
  email: z.string().min(1, 'Email is required').email('Please enter a valid email address'),
  password: z.string().min(1, 'Password is required'),
  remember: z.boolean().optional(),
});

/**
 * Register validation schema
 */
export const registerSchema = z
  .object({
    name: z.string().min(2, 'Name must be at least 2 characters').max(50, 'Name is too long'),
    email: z.string().min(1, 'Email is required').email('Please enter a valid email address'),
    password: z
      .string()
      .min(1, 'Password is required')
      .min(8, 'Password must be at least 8 characters')
      .regex(/[A-Z]/, 'Password must contain at least one uppercase letter')
      .regex(/[a-z]/, 'Password must contain at least one lowercase letter')
      .regex(/[0-9]/, 'Password must contain at least one number')
      .regex(/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/, 'Password must contain at least one special character'),
    password_confirmation: z.string().min(1, 'Please confirm your password'),
    accept_terms: z.boolean().refine((val) => val === true, 'You must accept the terms and conditions'),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: "Passwords don't match",
    path: ['password_confirmation'],
  });

/**
 * Forgot password validation schema
 */
export const forgotPasswordSchema = z.object({
  email: z.string().min(1, 'Email is required').email('Please enter a valid email address'),
});

/**
 * Reset password validation schema
 */
export const resetPasswordSchema = z
  .object({
    password: z
      .string()
      .min(1, 'Password is required')
      .min(8, 'Password must be at least 8 characters')
      .regex(/[A-Z]/, 'Password must contain at least one uppercase letter')
      .regex(/[a-z]/, 'Password must contain at least one lowercase letter')
      .regex(/[0-9]/, 'Password must contain at least one number')
      .regex(/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/, 'Password must contain at least one special character'),
    password_confirmation: z.string().min(1, 'Please confirm your password'),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: "Passwords don't match",
    path: ['password_confirmation'],
  });

/**
 * Email verification schema
 */
export const emailVerificationSchema = z.object({
  token: z.string().min(1, 'Verification token is required'),
  email: z.string().email('Invalid email address').optional(),
});
