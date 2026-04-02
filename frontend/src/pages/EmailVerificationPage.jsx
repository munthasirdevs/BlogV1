import { useState, useEffect } from 'react';
import { useNavigate, Link, useParams, useSearchParams, useLocation } from 'react-router-dom';
import { H1, Text, Button } from '@/components/atoms';
import { Alert } from '@/components/molecules';
import { useAuth } from '@/contexts/AuthContext';
import { useToast } from '@/contexts/ToastContext';
import { ROUTES } from '@/constants';
import { Mail, CheckCircle, XCircle, MailOpen, RefreshCw, Home, LogIn, Clock } from 'lucide-react';

/**
 * Email verification page component
 * Handles three states: pending, success, and failure
 */
function EmailVerificationPage() {
  const navigate = useNavigate();
  const location = useLocation();
  const [searchParams] = useSearchParams();
  const { verifyEmail, resendVerificationEmail } = useAuth();
  const toast = useToast();

  const [isLoading, setIsLoading] = useState(false);
  const [verificationState, setVerificationState] = useState('verifying'); // 'verifying', 'success', 'failure'
  const [userEmail, setUserEmail] = useState('');

  // Get token from URL for verification
  const token = searchParams.get('token');
  const emailFromLocation = location.state?.email;

  // Verify email on mount if token is present
  useEffect(() => {
    const verifyToken = async () => {
      if (!token) {
        // No token - show pending state
        setVerificationState('pending');
        if (emailFromLocation) {
          setUserEmail(emailFromLocation);
        }
        return;
      }

      setIsLoading(true);
      try {
        const result = await verifyEmail(token);
        if (result.success) {
          setVerificationState('success');
          toast.success('Email Verified!', 'Your email has been successfully verified.');
        } else {
          setVerificationState('failure');
          toast.error('Verification Failed', result.error || 'Unable to verify email.');
        }
      } catch (err) {
        setVerificationState('failure');
        const errorMessage = err.response?.data?.message || 'Unable to verify email.';
        toast.error('Verification Failed', errorMessage);
      } finally {
        setIsLoading(false);
      }
    };

    verifyToken();
  }, [token, verifyEmail, toast, emailFromLocation]);

  // Handle resend verification email
  const handleResendVerification = async () => {
    if (!userEmail) {
      toast.error('Email Required', 'Please provide your email address.');
      return;
    }

    setIsLoading(true);
    try {
      const result = await resendVerificationEmail(userEmail);
      if (result.success) {
        toast.success(
          'Verification Email Sent!',
          `Check your inbox at ${userEmail} for the verification link.`
        );
      } else {
        toast.error('Failed', result.error || 'Unable to resend verification email.');
      }
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Unable to resend verification email.';
      toast.error('Failed', errorMessage);
    } finally {
      setIsLoading(false);
    }
  };

  // Render based on verification state
  if (verificationState === 'verifying') {
    return (
      <div className="w-full max-w-md mx-auto text-center py-12">
        <div className="inline-flex items-center justify-center w-20 h-20 bg-primary-100 dark:bg-primary-900/30 rounded-full mb-6">
          <RefreshCw className="w-10 h-10 text-primary-600 dark:text-primary-400 animate-spin" />
        </div>
        <H1 className="mb-2">Verifying Your Email</H1>
        <Text color="muted">Please wait while we verify your email address...</Text>
      </div>
    );
  }

  if (verificationState === 'success') {
    return (
      <div className="w-full max-w-md mx-auto text-center py-12">
        <div className="inline-flex items-center justify-center w-20 h-20 bg-success-100 dark:bg-success-900/30 rounded-full mb-6">
          <CheckCircle className="w-10 h-10 text-success-600 dark:text-success-400" />
        </div>
        <H1 className="mb-2">Email Verified!</H1>
        <Text color="muted" className="mb-8">
          Your email has been successfully verified. You can now access all features.
        </Text>
        <div className="space-y-3">
          <Button 
            onClick={() => navigate(ROUTES.HOME)}
            className="w-full"
          >
            <Home className="w-4 h-4 mr-2" />
            Go to Home
          </Button>
          <Button 
            variant="outline"
            onClick={() => navigate(ROUTES.LOGIN)}
            className="w-full"
          >
            <LogIn className="w-4 h-4 mr-2" />
            Sign In
          </Button>
        </div>
      </div>
    );
  }

  if (verificationState === 'failure') {
    return (
      <div className="w-full max-w-md mx-auto text-center py-12">
        <div className="inline-flex items-center justify-center w-20 h-20 bg-danger-100 dark:bg-danger-900/30 rounded-full mb-6">
          <XCircle className="w-10 h-10 text-danger-600 dark:text-danger-400" />
        </div>
        <H1 className="mb-2">Verification Failed</H1>
        <Text color="muted" className="mb-6">
          This verification link is invalid or has expired. Please request a new one.
        </Text>
        <div className="space-y-3">
          <Button 
            onClick={() => navigate(ROUTES.FORGOT_PASSWORD)}
            className="w-full"
          >
            <Mail className="w-4 h-4 mr-2" />
            Request New Verification Link
          </Button>
          <Button 
            variant="outline"
            onClick={() => navigate(ROUTES.LOGIN)}
            className="w-full"
          >
            <LogIn className="w-4 h-4 mr-2" />
            Back to Sign In
          </Button>
        </div>
      </div>
    );
  }

  // Pending state - waiting for user to verify email
  return (
    <div className="w-full max-w-md mx-auto text-center py-12">
      <div className="inline-flex items-center justify-center w-20 h-20 bg-primary-100 dark:bg-primary-900/30 rounded-full mb-6">
        <MailOpen className="w-10 h-10 text-primary-600 dark:text-primary-400" />
      </div>
      <H1 className="mb-2">Check Your Email</H1>
      <Text color="muted" className="mb-8">
        We've sent a verification link to your email address.
        {userEmail && (
          <span className="block mt-2 font-medium text-secondary-700 dark:text-secondary-300">
            {userEmail}
          </span>
        )}
      </Text>

      {/* Info Box */}
      <div className="p-4 bg-secondary-50 dark:bg-secondary-800/50 rounded-lg mb-6 text-left">
        <div className="flex items-start gap-3">
          <Clock className="w-5 h-5 text-secondary-500 dark:text-secondary-400 flex-shrink-0 mt-0.5" />
          <div className="space-y-2 text-sm text-secondary-600 dark:text-secondary-400">
            <p>Click the link in the email to verify your account</p>
            <p>Check your spam folder if you don't see it</p>
            <p>The verification link expires in 24 hours</p>
          </div>
        </div>
      </div>

      {/* Resend Button */}
      <div className="space-y-3">
        <Button 
          onClick={handleResendVerification}
          isLoading={isLoading}
          disabled={isLoading || !userEmail}
          className="w-full"
        >
          <RefreshCw className="w-4 h-4 mr-2" />
          Resend Verification Email
        </Button>
        
        <Button 
          variant="outline"
          onClick={() => navigate(ROUTES.HOME)}
          className="w-full"
        >
          <Home className="w-4 h-4 mr-2" />
          Back to Home
        </Button>
      </div>

      {/* Already Verified */}
      <div className="mt-8">
        <Text color="muted" className="text-sm">
          Already verified?{' '}
          <Link
            to={ROUTES.LOGIN}
            className="text-primary-600 hover:text-primary-700 
                     dark:text-primary-400 dark:hover:text-primary-300 
                     font-medium hover:underline"
          >
            Sign in
          </Link>
        </Text>
      </div>
    </div>
  );
}

export default EmailVerificationPage;
