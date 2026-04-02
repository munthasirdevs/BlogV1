import { useState, useEffect } from 'react';
import { useNavigate, Link, useParams, useSearchParams } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { H1, Text, Input, Button } from '@/components/atoms';
import { Alert } from '@/components/molecules';
import { useAuth } from '@/contexts/AuthContext';
import { useToast } from '@/contexts/ToastContext';
import { resetPasswordSchema } from '@/utils/authValidation';
import { ROUTES } from '@/constants';
import { Lock, Eye, EyeOff, CheckCircle, XCircle, KeyRound } from 'lucide-react';

/**
 * Reset password page component with token validation
 */
function ResetPasswordPage() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const { resetPassword } = useAuth();
  const toast = useToast();
  
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [isValidating, setIsValidating] = useState(true);
  const [isTokenValid, setIsTokenValid] = useState(null);
  const [tokenError, setTokenError] = useState('');

  // Get token and email from URL params
  const token = searchParams.get('token');
  const email = searchParams.get('email');

  const {
    register,
    handleSubmit,
    formState: { errors },
    setError,
    clearErrors,
    watch,
  } = useForm({
    resolver: zodResolver(resetPasswordSchema),
    defaultValues: {
      password: '',
      password_confirmation: '',
    },
  });

  // Validate token on mount
  useEffect(() => {
    const validateToken = async () => {
      if (!token) {
        setIsTokenValid(false);
        setTokenError('Invalid or missing reset token');
        setIsValidating(false);
        return;
      }

      try {
        // Note: In a real implementation, you would validate the token with the backend
        // For now, we'll just check if the token exists
        // You can add a backend endpoint like POST /auth/validate-reset-token
        if (token && token.length > 10) {
          setIsTokenValid(true);
        } else {
          setIsTokenValid(false);
          setTokenError('Invalid or expired reset link');
        }
      } catch (err) {
        setIsTokenValid(false);
        setTokenError('This reset link is invalid or has expired');
      } finally {
        setIsValidating(false);
      }
    };

    validateToken();
  }, [token]);

  const onSubmit = async (data) => {
    if (!token) {
      toast.error('Invalid Token', 'Password reset token is missing');
      return;
    }

    setIsLoading(true);

    try {
      const result = await resetPassword({
        token,
        email: email || '',
        password: data.password,
        password_confirmation: data.password_confirmation,
      });

      if (result.success) {
        toast.success(
          'Password Reset Successful!',
          'Your password has been updated. You can now log in with your new password.'
        );
        
        // Redirect to login after a short delay
        setTimeout(() => {
          navigate(ROUTES.LOGIN);
        }, 2000);
      } else {
        setError('root', {
          type: 'manual',
          message: result.error || 'Failed to reset password. Please try again.',
        });
        toast.error('Failed', result.error || 'Please try again.');
      }
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'An unexpected error occurred. Please try again.';
      setError('root', { type: 'manual', message: errorMessage });
      toast.error('Failed', errorMessage);
    } finally {
      setIsLoading(false);
    }
  };

  // Clear errors when user starts typing
  const handleInputChange = () => {
    clearErrors('root');
  };

  // Loading state - validating token
  if (isValidating) {
    return (
      <div className="w-full max-w-md mx-auto text-center py-12">
        <div className="inline-flex items-center justify-center w-16 h-16 bg-secondary-100 dark:bg-secondary-800 rounded-full mb-4 animate-pulse">
          <KeyRound className="w-8 h-8 text-secondary-400" />
        </div>
        <H1 className="mb-2">Validating Reset Link</H1>
        <Text color="muted">Please wait...</Text>
      </div>
    );
  }

  // Invalid token state
  if (!isTokenValid) {
    return (
      <div className="w-full max-w-md mx-auto text-center py-12">
        <div className="inline-flex items-center justify-center w-16 h-16 bg-danger-100 dark:bg-danger-900/30 rounded-full mb-4">
          <XCircle className="w-8 h-8 text-danger-600 dark:text-danger-400" />
        </div>
        <H1 className="mb-2">Invalid Reset Link</H1>
        <Text color="muted" className="mb-6">
          {tokenError || 'This password reset link is invalid or has expired.'}
        </Text>
        <div className="space-y-3">
          <Button 
            onClick={() => navigate(ROUTES.FORGOT_PASSWORD)}
            className="w-full"
          >
            Request New Reset Link
          </Button>
          <Button 
            variant="outline"
            onClick={() => navigate(ROUTES.LOGIN)}
            className="w-full"
          >
            Back to Sign In
          </Button>
        </div>
      </div>
    );
  }

  // Valid token - show reset form
  return (
    <div className="w-full max-w-md mx-auto">
      {/* Header */}
      <div className="text-center mb-8">
        <div className="inline-flex items-center justify-center w-16 h-16 bg-primary-100 dark:bg-primary-900/30 rounded-full mb-4">
          <Lock className="w-8 h-8 text-primary-600 dark:text-primary-400" />
        </div>
        <H1 className="mb-2">Reset Password</H1>
        <Text color="muted">Enter your new password below</Text>
      </div>

      {/* Root Error Alert */}
      {errors.root && (
        <Alert variant="danger" className="mb-6" dismissible onDismiss={() => clearErrors('root')}>
          {errors.root.message}
        </Alert>
      )}

      {/* Reset Form */}
      <form onSubmit={handleSubmit(onSubmit)} className="space-y-5" noValidate>
        {/* Email Display (if available) */}
        {email && (
          <div className="p-3 bg-secondary-50 dark:bg-secondary-800/50 rounded-lg">
            <p className="text-sm text-secondary-600 dark:text-secondary-400">
              Resetting password for: <span className="font-medium">{email}</span>
            </p>
          </div>
        )}

        {/* Password Field */}
        <Input
          label="New Password"
          type={showPassword ? 'text' : 'password'}
          placeholder="Enter your new password"
          error={errors.password?.message}
          leftIcon={<Lock className="w-4 h-4" />}
          rightIcon={
            <button
              type="button"
              onClick={() => setShowPassword(!showPassword)}
              className="hover:text-secondary-600 dark:hover:text-secondary-400 transition-colors"
              tabIndex={-1}
              aria-label={showPassword ? 'Hide password' : 'Show password'}
            >
              {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
            </button>
          }
          disabled={isLoading}
          onChange={handleInputChange}
          autoComplete="new-password"
          {...register('password')}
        />

        {/* Confirm Password Field */}
        <Input
          label="Confirm New Password"
          type={showConfirmPassword ? 'text' : 'password'}
          placeholder="Confirm your new password"
          error={errors.password_confirmation?.message}
          leftIcon={<Lock className="w-4 h-4" />}
          rightIcon={
            <button
              type="button"
              onClick={() => setShowConfirmPassword(!showConfirmPassword)}
              className="hover:text-secondary-600 dark:hover:text-secondary-400 transition-colors"
              tabIndex={-1}
              aria-label={showConfirmPassword ? 'Hide password' : 'Show password'}
            >
              {showConfirmPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
            </button>
          }
          disabled={isLoading}
          onChange={handleInputChange}
          autoComplete="new-password"
          {...register('password_confirmation')}
        />

        {/* Password Requirements */}
        <div className="p-3 bg-secondary-50 dark:bg-secondary-800/50 rounded-lg space-y-2">
          <p className="text-xs font-medium text-secondary-700 dark:text-secondary-300">
            Password must contain:
          </p>
          <div className="grid grid-cols-2 gap-1">
            <p className="text-xs text-secondary-600 dark:text-secondary-400">• At least 8 characters</p>
            <p className="text-xs text-secondary-600 dark:text-secondary-400">• Uppercase letter</p>
            <p className="text-xs text-secondary-600 dark:text-secondary-400">• Lowercase letter</p>
            <p className="text-xs text-secondary-600 dark:text-secondary-400">• Number</p>
            <p className="text-xs text-secondary-600 dark:text-secondary-400">• Special character</p>
          </div>
        </div>

        {/* Submit Button */}
        <Button 
          type="submit" 
          className="w-full" 
          isLoading={isLoading}
          disabled={isLoading}
        >
          {isLoading ? 'Resetting...' : 'Reset Password'}
        </Button>
      </form>

      {/* Back to Login */}
      <div className="mt-8 text-center">
        <Link
          to={ROUTES.LOGIN}
          className="inline-flex items-center text-sm text-secondary-600 dark:text-secondary-400 
                   hover:text-secondary-700 dark:hover:text-secondary-300 transition-colors"
        >
          Back to Sign In
        </Link>
      </div>
    </div>
  );
}

export default ResetPasswordPage;
