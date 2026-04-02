import { Component } from 'react';
import { Button, H2, Text } from '@/components/atoms';
import { Alert } from '@/components/molecules';

/**
 * Error Boundary component to catch React errors
 */
class ErrorBoundary extends Component {
  constructor(props) {
    super(props);
    this.state = {
      hasError: false,
      error: null,
      errorInfo: null,
    };
  }

  static getDerivedStateFromError(error) {
    return { hasError: true };
  }

  componentDidCatch(error, errorInfo) {
    this.setState({ error, errorInfo });
    
    // Log error to error reporting service
    console.error('[ErrorBoundary] Caught error:', error, errorInfo);
    
    // You can send this to an error reporting service like Sentry
    // logErrorToService(error, errorInfo);
  }

  handleReset = () => {
    this.setState({ hasError: false, error: null, errorInfo: null });
    window.location.href = '/';
  };

  handleReload = () => {
    window.location.reload();
  };

  render() {
    if (this.state.hasError) {
      return (
        <div className="min-h-screen flex items-center justify-center p-4 bg-secondary-50 dark:bg-secondary-900">
          <div className="max-w-md w-full text-center">
            <Alert variant="danger" title="Something went wrong" className="mb-6">
              We're sorry, but something unexpected happened. Please try again.
            </Alert>

            <H2 className="mb-4">Oops!</H2>
            
            <Text className="mb-6" color="muted">
              The page encountered an error. You can try to reload the page or go back to the homepage.
            </Text>

            <div className="flex gap-3 justify-center">
              <Button onClick={this.handleReload} variant="primary">
                Reload Page
              </Button>
              <Button onClick={this.handleReset} variant="secondary">
                Go Home
              </Button>
            </div>

            {import.meta.env.DEV && this.state.error && (
              <details className="mt-8 text-left">
                <summary className="cursor-pointer text-sm font-medium text-secondary-600 dark:text-secondary-400 mb-2">
                  Error Details (Development)
                </summary>
                <pre className="p-4 bg-secondary-900 text-secondary-100 rounded-lg text-xs overflow-auto max-h-64">
                  {this.state.error.toString()}
                  {this.state.errorInfo?.componentStack}
                </pre>
              </details>
            )}
          </div>
        </div>
      );
    }

    return this.props.children;
  }
}

export default ErrorBoundary;
