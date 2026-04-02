import { useState } from 'react';
import { useNavigate, Link, useLocation } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { H1, Text, Input, Button, Checkbox } from '@/components/atoms';
import { Alert } from '@/components/molecules';
import { useAuth } from '@/contexts/AuthContext';
import { useToast } from '@/contexts/ToastContext';
import { loginSchema } from '@/utils/authValidation';
import { ROUTES } from '@/constants';
import { Mail, Lock, Eye, EyeOff, Github, Chrome } from 'lucide-react';

/**
 * Login page component with complete form validation
 */
function LoginPage() {
  const navigate = useNavigate();
  const location = useLocation();
  const { login } = useAuth();
  const toast = useToast();
  
  const [showPassword, setShowPassword] = useState(false);
  const [isLoading, setIsLoading] = useState(false);

  const from = location.state?.from?.pathname || ROUTES.HOME;

  const {
    register,
    handleSubmit,
    formState: { errors },
    setError,
    clearErrors,
    watch,
  } = useForm({
    resolver: zodResolver(loginSchema),
    defaultValues: {
      email: '',
      password: '',
      remember: false,
    },
  });

  const onSubmit = async (data) => {
    setIsLoading(true);

    try {
      const result = await login(data);
      if (result.success) {
        toast.success('Welcome back!', 'You have successfully logged in.');
        navigate(from, { replace: true });
      } else {
        setError('root', {
          type: 'manual',
          message: result.error || 'Login failed. Please check your credentials.',
        });
        toast.error('Login Failed', result.error || 'Please check your credentials and try again.');
      }
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'An unexpected error occurred. Please try again.';
      setError('root', { type: 'manual', message: errorMessage });
      toast.error('Login Failed', errorMessage);
    } finally {
      setIsLoading(false);
    }
  };

  // Clear errors when user starts typing
  const handleInputChange = () => {
    clearErrors('root');
  };

  // Handle social login (placeholder)
  const handleSocialLogin = (provider) => {
    toast.info('Coming Soon', `${provider} login will be available soon.`);
  };

  return (
    <div className="w-full max-w-md mx-auto">
      {/* Header */}
      <div className="text-center mb-8">
        <H1 className="mb-2">Welcome Back</H1>
        <Text color="muted">Sign in to your account to continue</Text>
      </div>

      {/* Root Error Alert */}
      {errors.root && (
        <Alert variant="danger" className="mb-6" dismissible onDismiss={() => clearErrors('root')}>
          {errors.root.message}
        </Alert>
      )}

      {/* Login Form */}
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

        {/* Password Field */}
        <Input
          label="Password"
          type={showPassword ? 'text' : 'password'}
          placeholder="Enter your password"
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
          autoComplete="current-password"
          {...register('password')}
        />

        {/* Remember Me & Forgot Password */}
        <div className="flex items-center justify-between">
          <label className="flex items-center gap-2 cursor-pointer group">
            <input
              type="checkbox"
              className="w-4 h-4 rounded border-secondary-300 dark:border-secondary-600 
                       text-primary-600 focus:ring-primary-500 focus:ring-2 
                       dark:bg-secondary-800 dark:checked:bg-primary-600
                       transition-colors"
              disabled={isLoading}
              {...register('remember')}
            />
            <span className="text-sm text-secondary-600 dark:text-secondary-400 group-hover:text-secondary-700 dark:group-hover:text-secondary-300 transition-colors">
              Remember me
            </span>
          </label>
          <Link
            to={ROUTES.FORGOT_PASSWORD}
            className="text-sm text-primary-600 hover:text-primary-700 
                     dark:text-primary-400 dark:hover:text-primary-300 
                     hover:underline transition-colors"
          >
            Forgot password?
          </Link>
        </div>

        {/* Submit Button */}
        <Button 
          type="submit" 
          className="w-full" 
          isLoading={isLoading}
          disabled={isLoading}
        >
          {isLoading ? 'Signing in...' : 'Sign In'}
        </Button>
      </form>

      {/* Divider */}
      <div className="relative my-6">
        <div className="absolute inset-0 flex items-center">
          <div className="w-full border-t border-secondary-200 dark:border-secondary-700"></div>
        </div>
        <div className="relative flex justify-center text-sm">
          <span className="px-4 bg-white dark:bg-secondary-900 text-secondary-500 dark:text-secondary-400">
            Or continue with
          </span>
        </div>
      </div>

      {/* Social Login Buttons */}
      <div className="grid grid-cols-2 gap-3">
        <Button
          type="button"
          variant="outline"
          className="w-full"
          onClick={() => handleSocialLogin('Google')}
          disabled={isLoading}
        >
          <Chrome className="w-4 h-4 mr-2" />
          Google
        </Button>
        <Button
          type="button"
          variant="outline"
          className="w-full"
          onClick={() => handleSocialLogin('GitHub')}
          disabled={isLoading}
        >
          <Github className="w-4 h-4 mr-2" />
          GitHub
        </Button>
      </div>

      {/* Register Link */}
      <Text className="mt-8 text-center" color="muted">
        Don't have an account?{' '}
        <Link
          to={ROUTES.REGISTER}
          className="text-primary-600 hover:text-primary-700 
                   dark:text-primary-400 dark:hover:text-primary-300 
                   font-medium hover:underline transition-colors"
        >
          Sign up
        </Link>
      </Text>
    </div>
  );
}

export default LoginPage;
