import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { H1, Text, Input, Button } from '@/components/atoms';
import { Alert, PasswordStrength } from '@/components/molecules';
import { useAuth } from '@/contexts/AuthContext';
import { useToast } from '@/contexts/ToastContext';
import { registerSchema } from '@/utils/authValidation';
import { ROUTES } from '@/constants';
import { Mail, Lock, User, Eye, EyeOff } from 'lucide-react';

/**
 * Register page component with password strength indicator
 */
function RegisterPage() {
  const navigate = useNavigate();
  const { register: registerUser } = useAuth();
  const toast = useToast();
  
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [isLoading, setIsLoading] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors },
    setError,
    clearErrors,
    watch,
  } = useForm({
    resolver: zodResolver(registerSchema),
    defaultValues: {
      name: '',
      email: '',
      password: '',
      password_confirmation: '',
      accept_terms: false,
    },
  });

  // Watch password for strength indicator
  const password = watch('password');

  const onSubmit = async (data) => {
    setIsLoading(true);

    const { password_confirmation, ...submitData } = data;

    try {
      const result = await registerUser(submitData);
      if (result.success) {
        toast.success('Account Created!', 'Welcome to the community. Please check your email to verify your account.');
        
        // If email verification is required, redirect to verification pending page
        if (result.requiresVerification) {
          navigate(ROUTES.VERIFY_EMAIL_PENDING, { 
            state: { email: data.email } 
          });
        } else {
          navigate(ROUTES.HOME);
        }
      } else {
        setError('root', {
          type: 'manual',
          message: result.error || 'Registration failed. Please try again.',
        });
        toast.error('Registration Failed', result.error || 'Please try again.');
      }
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'An unexpected error occurred. Please try again.';
      setError('root', { type: 'manual', message: errorMessage });
      toast.error('Registration Failed', errorMessage);
    } finally {
      setIsLoading(false);
    }
  };

  // Clear errors when user starts typing
  const handleInputChange = () => {
    clearErrors('root');
  };

  return (
    <div className="w-full max-w-md mx-auto">
      {/* Header */}
      <div className="text-center mb-8">
        <H1 className="mb-2">Create Account</H1>
        <Text color="muted">Join our community and start sharing</Text>
      </div>

      {/* Root Error Alert */}
      {errors.root && (
        <Alert variant="danger" className="mb-6" dismissible onDismiss={() => clearErrors('root')}>
          {errors.root.message}
        </Alert>
      )}

      {/* Registration Form */}
      <form onSubmit={handleSubmit(onSubmit)} className="space-y-5" noValidate>
        {/* Name Field */}
        <Input
          label="Full Name"
          type="text"
          placeholder="John Doe"
          error={errors.name?.message}
          leftIcon={<User className="w-4 h-4" />}
          disabled={isLoading}
          onChange={handleInputChange}
          autoComplete="name"
          {...register('name')}
        />

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

        {/* Password Field */}
        <Input
          label="Password"
          type={showPassword ? 'text' : 'password'}
          placeholder="Create a strong password"
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

        {/* Password Strength Indicator */}
        {password && (
          <div className="p-3 bg-secondary-50 dark:bg-secondary-800/50 rounded-lg">
            <PasswordStrength password={password} />
          </div>
        )}

        {/* Confirm Password Field */}
        <Input
          label="Confirm Password"
          type={showConfirmPassword ? 'text' : 'password'}
          placeholder="Confirm your password"
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

        {/* Terms Acceptance */}
        <div className="space-y-1">
          <label className="flex items-start gap-2 cursor-pointer group">
            <input
              type="checkbox"
              className="w-4 h-4 mt-0.5 rounded border-secondary-300 dark:border-secondary-600 
                       text-primary-600 focus:ring-primary-500 focus:ring-2 
                       dark:bg-secondary-800 dark:checked:bg-primary-600
                       transition-colors"
              disabled={isLoading}
              {...register('accept_terms')}
            />
            <span className="text-sm text-secondary-600 dark:text-secondary-400 group-hover:text-secondary-700 dark:group-hover:text-secondary-300 transition-colors">
              I agree to the{' '}
              <Link
                to={ROUTES.TERMS}
                className="text-primary-600 hover:text-primary-700 
                         dark:text-primary-400 dark:hover:text-primary-300 
                         hover:underline"
                target="_blank"
              >
                Terms of Service
              </Link>{' '}
              and{' '}
              <Link
                to={ROUTES.PRIVACY}
                className="text-primary-600 hover:text-primary-700 
                         dark:text-primary-400 dark:hover:text-primary-300 
                         hover:underline"
                target="_blank"
              >
                Privacy Policy
              </Link>
            </span>
          </label>
          {errors.accept_terms && (
            <p className="text-sm text-danger-600 dark:text-danger-400" role="alert">
              {errors.accept_terms.message}
            </p>
          )}
        </div>

        {/* Submit Button */}
        <Button 
          type="submit" 
          className="w-full" 
          isLoading={isLoading}
          disabled={isLoading}
        >
          {isLoading ? 'Creating Account...' : 'Create Account'}
        </Button>
      </form>

      {/* Login Link */}
      <Text className="mt-8 text-center" color="muted">
        Already have an account?{' '}
        <Link
          to={ROUTES.LOGIN}
          className="text-primary-600 hover:text-primary-700 
                   dark:text-primary-400 dark:hover:text-primary-300 
                   font-medium hover:underline transition-colors"
        >
          Sign in
        </Link>
      </Text>
    </div>
  );
}

export default RegisterPage;
