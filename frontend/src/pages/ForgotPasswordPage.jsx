import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { H1, Text, Input, Button } from '@/components/atoms';
import { Alert } from '@/components/molecules';
import { useAuth } from '@/contexts/AuthContext';
import { useToast } from '@/contexts/ToastContext';
import { forgotPasswordSchema } from '@/utils/authValidation';
import { ROUTES } from '@/constants';
import { Mail, ArrowLeft, MailCheck } from 'lucide-react';

/**
 * Forgot password page component
 */
function ForgotPasswordPage() {
  const { forgotPassword } = useAuth();
  const toast = useToast();
  
  const [isLoading, setIsLoading] = useState(false);
  const [isSubmitted, setIsSubmitted] = useState(false);
  const [submittedEmail, setSubmittedEmail] = useState('');

  const {
    register,
    handleSubmit,
    formState: { errors },
    setError,
    clearErrors,
  } = useForm({
    resolver: zodResolver(forgotPasswordSchema),
    defaultValues: {
      email: '',
    },
  });

  const onSubmit = async (data) => {
    setIsLoading(true);

    try {
      const result = await forgotPassword(data.email);
      if (result.success) {
        setIsSubmitted(true);
        setSubmittedEmail(data.email);
        toast.success(
          'Reset Email Sent!',
          `Check your inbox for password reset instructions.`
        );
      } else {
        setError('root', {
          type: 'manual',
          message: result.error || 'Failed to send reset email. Please try again.',
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

  // Reset the form to request another reset email
  const handleRequestAnother = () => {
    setIsSubmitted(false);
    setSubmittedEmail('');
  };

  return (
    <div className="w-full max-w-md mx-auto">
      {/* Header */}
      <div className="text-center mb-8">
        <div className="inline-flex items-center justify-center w-16 h-16 bg-primary-100 dark:bg-primary-900/30 rounded-full mb-4">
          <MailCheck className="w-8 h-8 text-primary-600 dark:text-primary-400" />
        </div>
        <H1 className="mb-2">Forgot Password?</H1>
        <Text color="muted">
          No worries! Enter your email and we'll send you reset instructions.
        </Text>
      </div>

      {/* Root Error Alert */}
      {errors.root && (
        <Alert variant="danger" className="mb-6" dismissible onDismiss={() => clearErrors('root')}>
          {errors.root.message}
        </Alert>
      )}

      {!isSubmitted ? (
        /* Request Reset Form */
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-5" noValidate>
          {/* Email Field */}
          <Input
            label="Email Address"
            type="email"
            placeholder="you@example.com"
            error={errors.email?.message}
            leftIcon={<Mail className="w-4 h-4" />}
            disabled={isLoading}
            onChange={handleInputChange}
            autoComplete="email"
            {...register('email')}
          />

          {/* Info Box */}
          <div className="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <p className="text-sm text-blue-700 dark:text-blue-300">
              Enter the email address associated with your account and we'll send you a link to reset your password.
            </p>
          </div>

          {/* Submit Button */}
          <Button 
            type="submit" 
            className="w-full" 
            isLoading={isLoading}
            disabled={isLoading}
          >
            {isLoading ? 'Sending...' : 'Send Reset Link'}
          </Button>
        </form>
      ) : (
        /* Success Message */
        <div className="space-y-6">
          <Alert variant="success" className="text-center">
            <div className="space-y-2">
              <p className="font-medium">Check your email!</p>
              <p className="text-sm">
                We've sent password reset instructions to{' '}
                <span className="font-semibold">{submittedEmail}</span>
              </p>
            </div>
          </Alert>

          <div className="p-4 bg-secondary-50 dark:bg-secondary-800/50 rounded-lg space-y-3">
            <p className="text-sm text-secondary-600 dark:text-secondary-400">
              Didn't receive the email?
            </p>
            <div className="space-y-2">
              <p className="text-xs text-secondary-500 dark:text-secondary-500">
                • Check your spam folder
              </p>
              <p className="text-xs text-secondary-500 dark:text-secondary-500">
                • Make sure you entered the correct email
              </p>
              <p className="text-xs text-secondary-500 dark:text-secondary-500">
                • The link expires in 1 hour
              </p>
            </div>
          </div>

          <div className="space-y-3">
            <Button 
              type="button" 
              variant="outline"
              className="w-full"
              onClick={handleRequestAnother}
              disabled={isLoading}
            >
              <Mail className="w-4 h-4 mr-2" />
              Request Another Reset Link
            </Button>
          </div>
        </div>
      )}

      {/* Back to Login */}
      <div className="mt-8 text-center">
        <Link
          to={ROUTES.LOGIN}
          className="inline-flex items-center text-sm text-secondary-600 dark:text-secondary-400 
                   hover:text-secondary-700 dark:hover:text-secondary-300 transition-colors"
        >
          <ArrowLeft className="w-4 h-4 mr-2" />
          Back to Sign In
        </Link>
      </div>
    </div>
  );
}

export default ForgotPasswordPage;
